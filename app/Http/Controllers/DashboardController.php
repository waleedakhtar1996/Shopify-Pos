<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $shop = Auth::user();

        $totalProducts = Product::where('user_id', $shop->id)->count();
        $activeProducts = Product::where('user_id', $shop->id)->where('status', 'active')->count();

        $totalOrders = Order::where('user_id', $shop->id)->count();
        $totalSales = Order::where('user_id', $shop->id)->sum('total_price');

        $totalCustomers = Customer::where('user_id', $shop->id)->count();

        $monthExpenses = Expense::where('user_id', $shop->id)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        $lowStockCount = Product::where('user_id', $shop->id)
            ->with('variants')
            ->get()
            ->filter(function ($p) {
                return $p->variants->sum('inventory_quantity') <= 5;
            })->count();

        $recentOrders = Order::where('user_id', $shop->id)
            ->latest('shopify_created_at')
            ->take(5)
            ->get();

        // Daily sales trend - last 7 days
        $salesRows = Order::where('user_id', $shop->id)
            ->where('shopify_created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(shopify_created_at) as day, SUM(total_price) as total')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $chartLabels = [];
        $chartTotals = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::parse($date)->format('M d');
            $chartTotals[] = isset($salesRows[$date]) ? (float) $salesRows[$date]->total : 0;
        }

        // Expense category breakdown
        $expenseCategories = \App\Models\ExpenseCategory::where('user_id', $shop->id)
            ->withSum('expenses', 'amount')
            ->get()
            ->filter(fn($c) => $c->expenses_sum_amount > 0);

        // Product status breakdown
        $draftProducts = Product::where('user_id', $shop->id)->where('status', 'draft')->count();
        $archivedProducts = Product::where('user_id', $shop->id)->where('status', 'archived')->count();

        return view('dashboard', compact(
            'totalProducts', 'activeProducts', 'totalOrders', 'totalSales',
            'totalCustomers', 'monthExpenses', 'lowStockCount', 'recentOrders',
            'chartLabels', 'chartTotals', 'expenseCategories', 'draftProducts', 'archivedProducts'
        ));
    }

    public function profile()
    {
        $shop = Auth::user();
        return view('profile', compact('shop'));
    }

    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $shop->id,
            'email' => 'nullable|email|max:255',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:4|confirmed',
        ]);

        if (!empty($validated['new_password'])) {
            if (empty($validated['current_password']) || !\Illuminate\Support\Facades\Hash::check($validated['current_password'], $shop->login_password)) {
                return back()->withInput()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            $shop->login_password = bcrypt($validated['new_password']);
        }

        $shop->username = $validated['username'];
        $shop->email = $validated['email'] ?? $shop->email;
        $shop->save();

        return redirect()->route('profile')->with('success', 'Profile updated!');
    }
}
