<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    protected function paymentTypes($shop)
    {
        $fromOrders = Order::where('user_id', $shop->id)
            ->whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method');

        $defaults = collect(['Cash', 'Bank Transfer']);

        return $fromOrders->merge($defaults)->unique()->sort()->values();
    }

    public function index(Request $request)
    {
        $shop = Auth::user();

        $query = Purchase::where('user_id', $shop->id)->withCount('items');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest('purchase_date');
                break;
            case 'total_high':
                $query->orderByDesc('total');
                break;
            case 'total_low':
                $query->orderBy('total');
                break;
            default:
                $query->latest('purchase_date');
        }

        $purchases = $query->paginate(20)->withQueryString();

        $totalPurchaseValue = Purchase::where('user_id', $shop->id)->sum('total');
        $totalPaid = Purchase::where('user_id', $shop->id)->sum('amount_paid');
        $totalUnpaid = Purchase::where('user_id', $shop->id)
            ->selectRaw('SUM(total - amount_paid) as unpaid')
            ->value('unpaid') ?? 0;

        return view('purchases.index', compact('purchases', 'totalPurchaseValue', 'totalPaid', 'totalUnpaid'));
    }

    public function create()
    {
        $shop = Auth::user();
        $nextNumber = 'PO-' . str_pad((Purchase::where('user_id', Auth::id())->count() + 1), 5, '0', STR_PAD_LEFT);
        $paymentTypes = $this->paymentTypes($shop);
        return view('purchases.create', compact('nextNumber', 'paymentTypes'));
    }

    public function searchProducts(Request $request)
    {
        $shop = Auth::user();
        $search = $request->get('q', '');

        $products = Product::where('user_id', $shop->id)
            ->with(['variants', 'images'])
            ->when($search, function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($vq) use ($search) {
                        $vq->where('sku', 'like', "%{$search}%");
                    });
            })
            ->limit(20)
            ->get();

        $results = [];
        foreach ($products as $product) {
            $image = $product->images->first()->src ?? null;
            foreach ($product->variants as $variant) {
                $variantLabel = trim(implode(' / ', array_filter([$variant->option1, $variant->option2, $variant->option3])));
                $results[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'title' => $product->title,
                    'variant_title' => $variantLabel ?: null,
                    'sku' => $variant->sku,
                    'image' => $image,
                    'price' => $variant->price,
                    'cost' => $variant->cost,
                    'inventory_quantity' => $variant->inventory_quantity,
                ];
            }
        }

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'purchase_number' => 'nullable|string|max:255',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'purchase_date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,unpaid,partial',
            'payment_type' => 'nullable|string|max:255',
            'amount_paid' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,received,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.product_title' => 'required|string',
            'items.*.variant_title' => 'nullable|string',
            'items.*.sku' => 'nullable|string',
            'items.*.image' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $item['quantity'] * $item['cost_price'];
        }

        $discount = $validated['discount'] ?? 0;
        $tax = $validated['tax'] ?? 0;
        $shipping = $validated['shipping_cost'] ?? 0;
        $total = $subtotal - $discount + $tax + $shipping;

        $amountPaid = $validated['amount_paid'] ?? 0;
        if ($validated['payment_status'] === 'paid') {
            $amountPaid = $total;
        } elseif ($validated['payment_status'] === 'unpaid') {
            $amountPaid = 0;
        }

        $purchase = Purchase::create([
            'user_id' => $shop->id,
            'purchase_number' => $validated['purchase_number'] ?? null,
            'supplier_name' => $validated['supplier_name'] ?? null,
            'supplier_contact' => $validated['supplier_contact'] ?? null,
            'purchase_date' => $validated['purchase_date'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping_cost' => $shipping,
            'total' => $total,
            'payment_status' => $validated['payment_status'],
            'payment_type' => $validated['payment_type'] ?? null,
            'amount_paid' => $amountPaid,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $item['product_id'] ?? null,
                'product_variant_id' => $item['variant_id'] ?? null,
                'product_title' => $item['product_title'],
                'variant_title' => $item['variant_title'] ?? null,
                'sku' => $item['sku'] ?? null,
                'image' => $item['image'] ?? null,
                'quantity' => $item['quantity'],
                'cost_price' => $item['cost_price'],
                'total' => $item['quantity'] * $item['cost_price'],
            ]);
        }

        return redirect()->route('purchases.index')->with('success', 'Purchase entry created!');
    }

    public function show(Purchase $purchase)
    {
        $shop = Auth::user();
        if ($purchase->user_id !== $shop->id) {
            abort(403);
        }
        $purchase->load('items');

        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $shop = Auth::user();
        if ($purchase->user_id !== $shop->id) {
            abort(403);
        }
        $purchase->load('items');
        $paymentTypes = $this->paymentTypes($shop);

        $existingItems = $purchase->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'product_title' => $item->product_title,
                'variant_title' => $item->variant_title,
                'sku' => $item->sku,
                'image' => $item->image,
                'quantity' => (float) $item->quantity,
                'cost_price' => (float) $item->cost_price,
            ];
        })->values();

        return view('purchases.edit', compact('purchase', 'paymentTypes', 'existingItems'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $shop = Auth::user();
        if ($purchase->user_id !== $shop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'purchase_number' => 'nullable|string|max:255',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'purchase_date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,unpaid,partial',
            'payment_type' => 'nullable|string|max:255',
            'amount_paid' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,received,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.product_title' => 'required|string',
            'items.*.variant_title' => 'nullable|string',
            'items.*.sku' => 'nullable|string',
            'items.*.image' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $item['quantity'] * $item['cost_price'];
        }

        $discount = $validated['discount'] ?? 0;
        $tax = $validated['tax'] ?? 0;
        $shipping = $validated['shipping_cost'] ?? 0;
        $total = $subtotal - $discount + $tax + $shipping;

        $amountPaid = $validated['amount_paid'] ?? 0;
        if ($validated['payment_status'] === 'paid') {
            $amountPaid = $total;
        } elseif ($validated['payment_status'] === 'unpaid') {
            $amountPaid = 0;
        }

        $purchase->update([
            'purchase_number' => $validated['purchase_number'] ?? null,
            'supplier_name' => $validated['supplier_name'] ?? null,
            'supplier_contact' => $validated['supplier_contact'] ?? null,
            'purchase_date' => $validated['purchase_date'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping_cost' => $shipping,
            'total' => $total,
            'payment_status' => $validated['payment_status'],
            'payment_type' => $validated['payment_type'] ?? null,
            'amount_paid' => $amountPaid,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $purchase->items()->delete();
        foreach ($validated['items'] as $item) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $item['product_id'] ?? null,
                'product_variant_id' => $item['variant_id'] ?? null,
                'product_title' => $item['product_title'],
                'variant_title' => $item['variant_title'] ?? null,
                'sku' => $item['sku'] ?? null,
                'image' => $item['image'] ?? null,
                'quantity' => $item['quantity'],
                'cost_price' => $item['cost_price'],
                'total' => $item['quantity'] * $item['cost_price'],
            ]);
        }

        return redirect()->route('purchases.index')->with('success', 'Purchase entry updated!');
    }

    public function destroy(Purchase $purchase)
    {
        $shop = Auth::user();
        if ($purchase->user_id !== $shop->id) {
            abort(403);
        }
        $purchase->delete();

        return redirect()->route('purchases.index')->with('success', 'Purchase entry deleted.');
    }
}
