<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function products()
    {
        $shop = Auth::user();

        $total = Product::where('user_id', $shop->id)->count();
        $active = Product::where('user_id', $shop->id)->where('status', 'active')->count();
        $draft = Product::where('user_id', $shop->id)->where('status', 'draft')->count();
        $archived = Product::where('user_id', $shop->id)->where('status', 'archived')->count();
        $totalVariants = Product::where('user_id', $shop->id)->withCount('variants')->get()->sum('variants_count');

        return view('reports.products', compact('total', 'active', 'draft', 'archived', 'totalVariants'));
    }

    public function sales()
    {
        $shop = Auth::user();

        $totalSales = Order::where('user_id', $shop->id)->sum('total_price');
        $totalOrders = Order::where('user_id', $shop->id)->count();
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
        $paidSales = Order::where('user_id', $shop->id)->where('financial_status', 'paid')->sum('total_price');
        $pendingSales = Order::where('user_id', $shop->id)->where('financial_status', 'pending')->sum('total_price');

        return view('reports.sales', compact('totalSales', 'totalOrders', 'avgOrderValue', 'paidSales', 'pendingSales'));
    }

    public function dailySales(Request $request)
    {
        $shop = Auth::user();
        $days = (int) $request->get('days', 30);

        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $rows = Order::where('user_id', $shop->id)
            ->where('shopify_created_at', '>=', $startDate)
            ->selectRaw('DATE(shopify_created_at) as day, SUM(total_price) as total, COUNT(*) as cnt')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $totals = [];
        $counts = [];
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($days - 1 - $i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('M d');
            $totals[] = isset($rows[$date]) ? (float) $rows[$date]->total : 0;
            $counts[] = isset($rows[$date]) ? (int) $rows[$date]->cnt : 0;
        }

        return view('reports.daily-sales', compact('labels', 'totals', 'counts', 'days'));
    }

    public function expenses()
    {
        $shop = Auth::user();

        $totalExpenses = Expense::where('user_id', $shop->id)->sum('amount');

        $categoryData = ExpenseCategory::where('user_id', $shop->id)
            ->withSum('expenses', 'amount')
            ->get()
            ->filter(fn($c) => $c->expenses_sum_amount > 0);

        $uncategorized = Expense::where('user_id', $shop->id)->whereNull('expense_category_id')->sum('amount');

        return view('reports.expenses', compact('totalExpenses', 'categoryData', 'uncategorized'));
    }

    protected function productSalesQuantities($shop)
    {
        return OrderItem::whereHas('order', function ($q) use ($shop) {
                $q->where('user_id', $shop->id);
            })
            ->selectRaw('title, SUM(quantity) as total_qty')
            ->groupBy('title')
            ->orderByDesc('total_qty')
            ->get();
    }

    public function slowMoving()
    {
        $shop = Auth::user();
        $sold = $this->productSalesQuantities($shop)->sortBy('total_qty')->take(10);

        $allProducts = Product::where('user_id', $shop->id)->pluck('title');
        $soldTitles = $sold->pluck('title')->toArray();
        $neverSold = $allProducts->diff($soldTitles)->take(10 - $sold->count());

        return view('reports.slow-moving', compact('sold', 'neverSold'));
    }

    public function fastMoving()
    {
        $shop = Auth::user();
        $sold = $this->productSalesQuantities($shop)->take(10);

        return view('reports.fast-moving', compact('sold'));
    }

    public function pnl(Request $request)
    {
        $shop = Auth::user();

        $totalSales = Order::where('user_id', $shop->id)->sum('total_price');
        $totalExpenses = Expense::where('user_id', $shop->id)->sum('amount');
        $profit = $totalSales - $totalExpenses;

        // Monthly breakdown, last 6 months
        $months = [];
        $salesData = [];
        $expenseData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');

            $salesData[] = (float) Order::where('user_id', $shop->id)
                ->whereYear('shopify_created_at', $month->year)
                ->whereMonth('shopify_created_at', $month->month)
                ->sum('total_price');

            $expenseData[] = (float) Expense::where('user_id', $shop->id)
                ->whereYear('expense_date', $month->year)
                ->whereMonth('expense_date', $month->month)
                ->sum('amount');
        }

        return view('reports.pnl', compact('totalSales', 'totalExpenses', 'profit', 'months', 'salesData', 'expenseData'));
    }

    public function stockValue()
    {
        $shop = Auth::user();

        $products = Product::where('user_id', $shop->id)->with('variants')->get();

        $totalValue = 0;
        $totalUnits = 0;
        $rows = [];

        foreach ($products as $product) {
            $qty = $product->variants->sum('inventory_quantity');
            $avgPrice = $product->variants->avg('price') ?? 0;
            $value = $product->variants->sum(function ($v) {
                return ($v->inventory_quantity ?? 0) * ($v->price ?? 0);
            });
            $totalValue += $value;
            $totalUnits += $qty;

            if ($qty > 0) {
                $rows[] = [
                    'title' => $product->title,
                    'qty' => $qty,
                    'avg_price' => $avgPrice,
                    'value' => $value,
                ];
            }
        }

        usort($rows, fn($a, $b) => $b['value'] <=> $a['value']);

        return view('reports.stock-value', compact('totalValue', 'totalUnits', 'rows'));
    }

    public function categoryStock()
    {
        $shop = Auth::user();

        $products = Product::where('user_id', $shop->id)->with('variants')->get();

        $grouped = $products->groupBy(function ($p) {
            return $p->product_type ?: 'Uncategorized';
        })->map(function ($group) {
            return $group->sum(function ($p) {
                return $p->variants->sum('inventory_quantity');
            });
        })->sortDesc();

        return view('reports.category-stock', compact('grouped'));
    }

    public function barcodeInventory(Request $request)
    {
        $shop = Auth::user();

        $query = Product::where('user_id', $shop->id)
            ->whereHas('variants', function ($q) {
                $q->whereNotNull('barcode')->where('barcode', '!=', '');
            })
            ->with(['variants' => function ($q) {
                $q->whereNotNull('barcode')->where('barcode', '!=', '');
            }]);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(20)->withQueryString();
        $withBarcodeCount = Product::where('user_id', $shop->id)
            ->whereHas('variants', function ($q) {
                $q->whereNotNull('barcode')->where('barcode', '!=', '');
            })->count();
        $totalProducts = Product::where('user_id', $shop->id)->count();

        return view('reports.barcode-inventory', compact('products', 'withBarcodeCount', 'totalProducts'));
    }

    public function paymentType()
    {
        $shop = Auth::user();

        $data = Order::where('user_id', $shop->id)
            ->selectRaw('COALESCE(payment_method, "unknown") as method, COUNT(*) as cnt, SUM(total_price) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $totalSales = $data->sum('total');

        return view('reports.payment-type', compact('data', 'totalSales'));
    }

    public function returns()
    {
        $shop = Auth::user();

        $returns = Order::where('user_id', $shop->id)
            ->whereIn('financial_status', ['refunded', 'partially_refunded'])
            ->with('items')
            ->latest('shopify_created_at')
            ->paginate(20);

        $totalReturns = Order::where('user_id', $shop->id)
            ->whereIn('financial_status', ['refunded', 'partially_refunded'])
            ->count();

        $totalReturnedValue = Order::where('user_id', $shop->id)
            ->whereIn('financial_status', ['refunded', 'partially_refunded'])
            ->sum('total_price');

        return view('reports.returns', compact('returns', 'totalReturns', 'totalReturnedValue'));
    }
}
