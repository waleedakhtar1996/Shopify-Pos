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
    protected function getDateRange(Request $request)
    {
        $range = $request->get('range', 'all');
        $end = Carbon::now()->endOfDay();

        switch ($range) {
            case '7days':
                $start = Carbon::now()->subDays(6)->startOfDay();
                $label = 'Last 7 Days';
                break;
            case '15days':
                $start = Carbon::now()->subDays(14)->startOfDay();
                $label = 'Last 15 Days';
                break;
            case 'weekly':
                $start = Carbon::now()->startOfWeek();
                $label = 'This Week';
                break;
            case 'monthly':
                $start = Carbon::now()->startOfMonth();
                $label = 'This Month';
                break;
            default:
                $range = 'all';
                $start = null;
                $label = 'All Time';
        }

        return [$start, $end, $range, $label];
    }

    protected function applyDateFilter($query, $column, $start, $end)
    {
        if ($start) {
            $query->whereBetween($column, [$start, $end]);
        }
        return $query;
    }

    public function products(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $base = Product::where('user_id', $shop->id);
        if ($start && $this->columnExists('products', 'shopify_created_at')) {
            $base->whereBetween('shopify_created_at', [$start, $end]);
        }

        $total = (clone $base)->count();
        $active = (clone $base)->where('status', 'active')->count();
        $draft = (clone $base)->where('status', 'draft')->count();
        $archived = (clone $base)->where('status', 'archived')->count();
        $unlisted = (clone $base)->where('status', 'unlisted')->count();
        $totalVariants = (clone $base)->withCount('variants')->get()->sum('variants_count');

        return view('reports.products', compact('total', 'active', 'draft', 'archived', 'unlisted', 'totalVariants', 'range', 'rangeLabel'));
    }

    public function sales(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $base = Order::where('user_id', $shop->id);
        $this->applyDateFilter($base, 'shopify_created_at', $start, $end);

        $totalSales = (clone $base)->sum('total_price');
        $totalOrders = (clone $base)->count();
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
        $paidSales = (clone $base)->where('financial_status', 'paid')->sum('total_price');
        $pendingSales = (clone $base)->where('financial_status', 'pending')->sum('total_price');

        return view('reports.sales', compact('totalSales', 'totalOrders', 'avgOrderValue', 'paidSales', 'pendingSales', 'range', 'rangeLabel'));
    }

    public function dailySales(Request $request)
    {
        $shop = Auth::user();

        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);
        $daysMap = ['7days' => 7, '15days' => 15, 'weekly' => 7, 'monthly' => 30, 'all' => (int) $request->get('days', 30)];
        $days = $daysMap[$range] ?? (int) $request->get('days', 30);

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

        return view('reports.daily-sales', compact('labels', 'totals', 'counts', 'days', 'range', 'rangeLabel'));
    }

    public function expenses(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $base = Expense::where('user_id', $shop->id);
        $this->applyDateFilter($base, 'expense_date', $start, $end);

        $totalExpenses = (clone $base)->sum('amount');

        $categoryData = ExpenseCategory::where('user_id', $shop->id)
            ->with(['expenses' => function ($q) use ($start, $end) {
                $this->applyDateFilter($q, 'expense_date', $start, $end);
            }])
            ->get()
            ->map(function ($c) {
                $c->expenses_sum_amount = $c->expenses->sum('amount');
                return $c;
            })
            ->filter(fn($c) => $c->expenses_sum_amount > 0);

        $uncategorized = (clone $base)->whereNull('expense_category_id')->sum('amount');

        return view('reports.expenses', compact('totalExpenses', 'categoryData', 'uncategorized', 'range', 'rangeLabel'));
    }

    protected function productSalesQuantities($shop, $start = null, $end = null)
    {
        return OrderItem::whereHas('order', function ($q) use ($shop, $start, $end) {
                $q->where('user_id', $shop->id);
                $this->applyDateFilter($q, 'shopify_created_at', $start, $end);
            })
            ->selectRaw('title, SUM(quantity) as total_qty')
            ->groupBy('title')
            ->orderByDesc('total_qty')
            ->get();
    }

    public function slowMoving(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $sold = $this->productSalesQuantities($shop, $start, $end)->sortBy('total_qty')->take(10);

        $allProducts = Product::where('user_id', $shop->id)->pluck('title');
        $soldTitles = $sold->pluck('title')->toArray();
        $neverSold = $allProducts->diff($soldTitles)->take(10 - $sold->count());

        return view('reports.slow-moving', compact('sold', 'neverSold', 'range', 'rangeLabel'));
    }

    public function fastMoving(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $sold = $this->productSalesQuantities($shop, $start, $end)->take(10);

        return view('reports.fast-moving', compact('sold', 'range', 'rangeLabel'));
    }

    public function pnl(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $salesBase = Order::where('user_id', $shop->id);
        $expBase = Expense::where('user_id', $shop->id);
        $this->applyDateFilter($salesBase, 'shopify_created_at', $start, $end);
        $this->applyDateFilter($expBase, 'expense_date', $start, $end);

        $totalSales = (clone $salesBase)->sum('total_price');
        $totalRefunds = (clone $salesBase)->sum('total_refunded');
        $totalExpenses = (clone $expBase)->sum('amount');
        $profit = $totalSales - $totalRefunds - $totalExpenses;

        $months = [];
        $salesData = [];
        $expenseData = [];
        $refundData = [];
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

            $refundData[] = (float) Order::where('user_id', $shop->id)
                ->whereYear('shopify_created_at', $month->year)
                ->whereMonth('shopify_created_at', $month->month)
                ->sum('total_refunded');
        }

        return view('reports.pnl', compact('totalSales', 'totalRefunds', 'totalExpenses', 'profit', 'months', 'salesData', 'expenseData', 'refundData', 'range', 'rangeLabel'));
    }

    public function stockValue(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $collections = \App\Models\Collection::where('user_id', $shop->id)
            ->withCount('products')
            ->orderBy('title')
            ->get();

        $productsQuery = Product::where('user_id', $shop->id)->with('variants');

        if ($request->filled('collection_id') && $request->collection_id !== 'all') {
            $collectionId = $request->collection_id;
            $productsQuery->whereHas('collections', function ($q) use ($collectionId) {
                $q->where('collections.id', $collectionId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $productsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($vq) use ($search) {
                        $vq->where('sku', 'like', "%{$search}%");
                    });
            });
        }

        $products = $productsQuery->get();

        $totalValue = 0;
        $totalCostValue = 0;
        $totalUnits = 0;
        $rows = [];

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $qty = $variant->inventory_quantity ?? 0;
                if ($qty <= 0) {
                    continue;
                }

                if ($request->filled('search')) {
                    $search = strtolower($request->search);
                    $matchesTitle = str_contains(strtolower($product->title), $search);
                    $matchesSku = $variant->sku && str_contains(strtolower($variant->sku), $search);
                    if (!$matchesTitle && !$matchesSku) {
                        continue;
                    }
                }

                $price = $variant->price ?? 0;
                $cost = $variant->cost;
                $value = $qty * $price;
                $costValue = $cost !== null ? $qty * $cost : null;

                $totalValue += $value;
                $totalUnits += $qty;
                if ($costValue !== null) {
                    $totalCostValue += $costValue;
                }

                $variantLabel = trim(implode(' / ', array_filter([$variant->option1, $variant->option2, $variant->option3])));

                $rows[] = [
                    'title' => $product->title,
                    'variant' => $variantLabel ?: '-',
                    'sku' => $variant->sku,
                    'qty' => $qty,
                    'price' => $price,
                    'cost' => $cost,
                    'value' => $value,
                    'cost_value' => $costValue,
                ];
            }
        }

        usort($rows, fn($a, $b) => $b['value'] <=> $a['value']);

        return view('reports.stock-value', compact('totalValue', 'totalCostValue', 'totalUnits', 'rows', 'range', 'rangeLabel', 'collections'));
    }

    public function categoryStock(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $collections = \App\Models\Collection::where('user_id', $shop->id)
            ->with('products.variants')
            ->get();

        $grouped = $collections->mapWithKeys(function ($collection) {
            $qty = $collection->products->sum(function ($p) {
                return $p->variants->sum('inventory_quantity');
            });
            return [$collection->title => $qty];
        })->filter(function ($qty) {
            return $qty > 0;
        })->sortDesc();

        $collectionProductIds = $collections->pluck('products')->flatten()->pluck('id')->unique();
        $uncategorizedQty = Product::where('user_id', $shop->id)
            ->whereNotIn('id', $collectionProductIds)
            ->with('variants')
            ->get()
            ->sum(function ($p) {
                return $p->variants->sum('inventory_quantity');
            });

        if ($uncategorizedQty > 0) {
            $grouped->put('Uncategorized', $uncategorizedQty);
            $grouped = $grouped->sortDesc();
        }

        return view('reports.category-stock', compact('grouped', 'range', 'rangeLabel'));
    }

    public function barcodeInventory(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

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

        return view('reports.barcode-inventory', compact('products', 'withBarcodeCount', 'totalProducts', 'range', 'rangeLabel'));
    }

    public function paymentType(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $base = Order::where('user_id', $shop->id)
            ->whereIn('financial_status', ['paid', 'partially_refunded', 'partially_paid']);
        $this->applyDateFilter($base, 'shopify_created_at', $start, $end);

        $salesRows = (clone $base)
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method as method, COUNT(*) as cnt, SUM(total_price) as total')
            ->groupBy('method')
            ->get()
            ->keyBy(fn($r) => strtolower($r->method));

        $expBase = \App\Models\Expense::where('user_id', $shop->id)->whereNotNull('payment_type');
        $this->applyDateFilter($expBase, 'expense_date', $start, $end);

        $expenseRows = (clone $expBase)
            ->selectRaw('payment_type, SUM(amount) as total_expense')
            ->groupBy('payment_type')
            ->get()
            ->keyBy(fn($r) => strtolower($r->payment_type));

        $purchaseBase = \App\Models\Purchase::where('user_id', $shop->id)->whereNotNull('payment_type');
        $this->applyDateFilter($purchaseBase, 'purchase_date', $start, $end);

        $purchaseRows = (clone $purchaseBase)
            ->selectRaw('payment_type, SUM(total) as total_purchase')
            ->groupBy('payment_type')
            ->get()
            ->keyBy(fn($r) => strtolower($r->payment_type));

        $labelMap = [
            'manual' => 'Manually Marked Paid',
            'cash' => 'Cash',
            'bogus' => 'Test Gateway',
        ];

        // Union of every payment type seen in orders, expenses, or purchases
        $allKeys = $salesRows->keys()->merge($expenseRows->keys())->merge($purchaseRows->keys())->unique();

        $data = $allKeys->map(function ($key) use ($salesRows, $expenseRows, $purchaseRows, $labelMap) {
            $saleRow = $salesRows->get($key);
            $expRow = $expenseRows->get($key);
            $purRow = $purchaseRows->get($key);

            $originalMethod = $saleRow->method ?? ($expRow->payment_type ?? ($purRow->payment_type ?? $key));
            $total = $saleRow->total ?? 0;
            $cnt = $saleRow->cnt ?? 0;
            $expenseTotal = $expRow->total_expense ?? 0;
            $purchaseTotal = $purRow->total_purchase ?? 0;

            return (object) [
                'method' => $originalMethod,
                'display_method' => $labelMap[$key] ?? $originalMethod,
                'cnt' => $cnt,
                'total' => (float) $total,
                'expense_total' => (float) $expenseTotal,
                'purchase_total' => (float) $purchaseTotal,
                'remaining' => (float) $total - (float) $expenseTotal - (float) $purchaseTotal,
            ];
        })->sortByDesc('total')->values();

        $totalSales = $data->sum('total');
        $totalExpensesForPaymentTypes = $data->sum('expense_total');
        $totalPurchasesForPaymentTypes = $data->sum('purchase_total');
        $totalRemaining = $totalSales - $totalExpensesForPaymentTypes - $totalPurchasesForPaymentTypes;

        return view('reports.payment-type', compact('data', 'totalSales', 'totalExpensesForPaymentTypes', 'totalPurchasesForPaymentTypes', 'totalRemaining', 'range', 'rangeLabel'));
    }

    public function returns(Request $request)
    {
        $shop = Auth::user();
        [$start, $end, $range, $rangeLabel] = $this->getDateRange($request);

        $base = Order::where('user_id', $shop->id)
            ->whereIn('financial_status', ['refunded', 'partially_refunded']);
        $this->applyDateFilter($base, 'shopify_created_at', $start, $end);

        $returns = (clone $base)->with('items')->latest('shopify_created_at')->paginate(20)->withQueryString();
        $totalReturns = (clone $base)->count();
        $totalReturnedValue = (clone $base)->sum('total_price');

        $freqSeconds = $shop->returns_sync_frequency * 60;
        $lastSync = $shop->returns_last_synced_at;
        $secondsElapsed = $lastSync ? now()->diffInSeconds($lastSync) : $freqSeconds;
        $secondsRemaining = max(0, $freqSeconds - $secondsElapsed);

        return view('reports.returns', compact('returns', 'totalReturns', 'totalReturnedValue', 'secondsRemaining', 'freqSeconds', 'range', 'rangeLabel'));
    }

    public function returnsSyncAjax(Request $request, \App\Services\SalesSyncService $salesSyncService)
    {
        $shop = Auth::user();

        try {
            $salesSyncService->syncOrders($shop);
            $shop->returns_last_synced_at = now();
            $shop->save();

            return response()->json(['success' => true, 'message' => 'Returns synced']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function columnExists($table, $column)
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (!isset($cache[$key])) {
            $cache[$key] = \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
        }
        return $cache[$key];
    }
}
