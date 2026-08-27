@extends('layouts.app')

@section('title', 'Order Detail')

@section('content')
<style>
    .card { background:white; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .card h3 { margin-top:0; font-size:15px; }
    .info-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:15px; }
    .info-item label { display:block; font-size:12px; color:#888; margin-bottom:4px; }
    .info-item span { font-weight:600; font-size:14px; }
    table.items-table { width:100%; border-collapse:collapse; margin-top:10px; }
    table.items-table th { text-align:left; padding:8px; background:#f5f5f5; font-size:12px; color:#555; }
    table.items-table td { padding:8px; border-bottom:1px solid #eee; font-size:14px; }
    .back-link { color:#1a56db; text-decoration:none; font-size:14px; }
    .badge { padding:3px 10px; border-radius:10px; font-size:12px; font-weight:600; text-transform:capitalize; }
    .badge-paid, .badge-fulfilled { background:#d4edda; color:#155724; }
    .badge-pending, .badge-partial { background:#fff3cd; color:#856404; }
    .badge-refunded, .badge-unfulfilled { background:#f8d7da; color:#721c24; }
</style>

<a href="{{ route('sales.index') }}" class="back-link">&larr; Back to Orders</a>
<h1>Order {{ $order->order_number }}</h1>

<div class="card">
    <h3>Order Info</h3>
    <div class="info-grid">
        <div class="info-item">
            <label>Date</label>
            <span>{{ $order->shopify_created_at?->format('M d, Y H:i') }}</span>
        </div>
        <div class="info-item">
            <label>Financial Status</label>
            <span class="badge {{ $order->financial_status == 'paid' ? 'badge-paid' : ($order->financial_status == 'refunded' || $order->financial_status == 'partially_refunded' ? 'badge-refunded' : 'badge-pending') }}">{{ $order->financial_status ?? 'unknown' }}</span>
        </div>
        <div class="info-item">
            <label>Fulfillment Status</label>
            <span class="badge {{ $order->fulfillment_status == 'fulfilled' ? 'badge-fulfilled' : 'badge-unfulfilled' }}">{{ $order->fulfillment_status ?? 'unfulfilled' }}</span>
        </div>
        <div class="info-item">
            <label>Payment Method</label>
            <span>{{ $order->payment_method ?? '-' }}</span>
        </div>
    </div>
</div>

<div class="card">
    <h3>Customer</h3>
    <div class="info-grid">
        <div class="info-item">
            <label>Name</label>
            <span>{{ $order->customer_name ?? '-' }}</span>
        </div>
        <div class="info-item">
            <label>Email</label>
            <span>{{ $order->customer_email ?? '-' }}</span>
        </div>
        <div class="info-item">
            <label>Shipping Address</label>
            <span>{{ $order->shipping_address ?? '-' }}</span>
        </div>
    </div>
</div>

<div class="card">
    <h3>Items</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Variant</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->variant_title ?? '-' }}</td>
                    <td>{{ $item->sku ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($item->price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Totals</h3>
    <div class="info-grid">
        <div class="info-item">
            <label>Subtotal</label>
            <span>{{ $currencySymbol }}{{ number_format($order->subtotal_price, 2) }}</span>
        </div>
        <div class="info-item">
            <label>Tax</label>
            <span>{{ $currencySymbol }}{{ number_format($order->total_tax, 2) }}</span>
        </div>
        <div class="info-item">
            <label>Discounts</label>
            <span>{{ $currencySymbol }}{{ number_format($order->total_discounts, 2) }}</span>
        </div>
        <div class="info-item">
            <label>Total</label>
            <span style="font-size:18px; color:#008060;">{{ $currencySymbol }}{{ number_format($order->total_price, 2) }}</span>
        </div>
    </div>
</div>
@endsection
