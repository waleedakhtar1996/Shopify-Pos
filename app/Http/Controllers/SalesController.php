<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Services\SalesSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();

        $query = Order::with(["items", "customer"])
            ->where("user_id", $shop->id);

        if ($request->filled("search")) {
            $query->where(function($q) use ($request) {
                $q->where("order_number", "like", "%" . $request->search . "%")
                  ->orWhere("customer_name", "like", "%" . $request->search . "%")
                  ->orWhere("customer_email", "like", "%" . $request->search . "%");
            });
        }

        if ($request->filled("financial_status") && $request->financial_status !== "all") {
            $query->where("financial_status", $request->financial_status);
        }

        if ($request->filled("fulfillment_status") && $request->fulfillment_status !== "all") {
            $query->where("fulfillment_status", $request->fulfillment_status);
        }

        $sort = $request->get("sort", "newest");
        switch ($sort) {
            case "oldest":
                $query->oldest("shopify_created_at");
                break;
            case "total_high":
                $query->orderByDesc("total_price");
                break;
            case "total_low":
                $query->orderBy("total_price");
                break;
            default:
                $query->latest("shopify_created_at");
        }

        $orders = $query->paginate(20)->withQueryString();

        $freqSeconds = $shop->orders_sync_frequency * 60;
        $lastSync = $shop->orders_last_synced_at;
        $secondsElapsed = $lastSync ? now()->diffInSeconds($lastSync) : $freqSeconds;
        $secondsRemaining = max(0, $freqSeconds - $secondsElapsed);

        return view("sales.index", compact("orders", "secondsRemaining", "freqSeconds"));
    }

    public function show(Order $order)
    {
        $shop = Auth::user();

        if ($order->user_id !== $shop->id) {
            abort(403);
        }

        $order->load(['items', 'customer']);

        return view('sales.show', compact('order'));
    }

    public function customers(Request $request)
    {
        $shop = Auth::user();

        $query = Customer::where("user_id", $shop->id);

        if ($request->filled("search")) {
            $query->where(function($q) use ($request) {
                $q->where("first_name", "like", "%" . $request->search . "%")
                  ->orWhere("last_name", "like", "%" . $request->search . "%")
                  ->orWhere("email", "like", "%" . $request->search . "%");
            });
        }

        $sort = $request->get("sort", "total_high");
        switch ($sort) {
            case "total_low":
                $query->orderBy("total_spent");
                break;
            case "orders_high":
                $query->orderByDesc("orders_count");
                break;
            case "name_asc":
                $query->orderBy("first_name");
                break;
            default:
                $query->orderByDesc("total_spent");
        }

        $customers = $query->paginate(20)->withQueryString();

        $freqSeconds = $shop->customers_sync_frequency * 60;
        $lastSync = $shop->customers_last_synced_at;
        $secondsElapsed = $lastSync ? now()->diffInSeconds($lastSync) : $freqSeconds;
        $secondsRemaining = max(0, $freqSeconds - $secondsElapsed);

        return view("sales.customers", compact("customers", "secondsRemaining", "freqSeconds"));
    }

    public function sync(Request $request, SalesSyncService $salesSyncService)
    {
        $shop = Auth::user();

        try {
            $orderCount = $salesSyncService->syncOrders($shop);
            $customerCount = $salesSyncService->syncCustomers($shop);

            $shop->orders_last_synced_at = now();
            $shop->customers_last_synced_at = now();
            $shop->save();

            return redirect()->route('sales.index')
                ->with('success', "Synced {$orderCount} orders and {$customerCount} customers from Shopify!");
        } catch (\Exception $e) {
            return redirect()->route('sales.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function syncAjax(Request $request, \App\Services\SalesSyncService $salesSyncService)
    {
        $shop = Auth::user();

        try {
            $orderCount = $salesSyncService->syncOrders($shop);
            $customerCount = $salesSyncService->syncCustomers($shop);

            $shop->orders_last_synced_at = now();
            $shop->customers_last_synced_at = now();
            $shop->save();

            return response()->json([
                'success' => true,
                'message' => "Synced {$orderCount} orders and {$customerCount} customers",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
