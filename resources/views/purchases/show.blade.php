@extends('layouts.app')

@section('title', 'Purchase Detail')

@section('content')
<style>
    .card { background:white; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .card h3 { margin-top:0; font-size:15px; }
    .info-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:15px; }
    .info-item label { display:block; font-size:12px; color:#888; margin-bottom:4px; }
    .info-item span { font-weight:600; font-size:14px; }
    table.items-table { width:100%; border-collapse:collapse; margin-top:10px; }
    table.items-table th { text-align:left; padding:8px; background:#f5f5f5; font-size:12px; color:#555; }
    table.items-table td { padding:8px; border-bottom:1px solid #eee; font-size:14px; vertical-align:middle; }
    table.items-table img { width:36px; height:36px; object-fit:cover; border-radius:4px; background:#f0f0f0; }
    .back-link { color:#1a56db; text-decoration:none; font-size:14px; }
    .badge { padding:3px 10px; border-radius:10px; font-size:12px; font-weight:600; text-transform:capitalize; }
    .badge-paid, .badge-received { background:#d4edda; color:#155724; }
    .badge-partial, .badge-pending { background:#fff3cd; color:#856404; }
    .badge-unpaid, .badge-cancelled { background:#f8d7da; color:#721c24; }
    .totals-box { max-width:340px; margin-left:auto; }
    .totals-box .trow { display:flex; justify-content:space-between; padding:6px 0; font-size:14px; }
    .totals-box .trow.grand { font-weight:700; font-size:16px; border-top:1px solid #ddd; padding-top:10px; margin-top:6px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
</style>

<a href="{{ route('purchases.index') }}" class="back-link">&larr; Back to Purchases</a>
<h1>Purchase {{ $purchase->purchase_number ?? '#' . $purchase->id }}</h1>

<div class="card">
    <h3>Purchase Info</h3>
    <div class="info-grid">
        <div class="info-item">
            <label>Date</label>
            <span>{{ $purchase->purchase_date->format('M d, Y') }}</span>
        </div>
        <div class="info-item">
            <label>Supplier</label>
            <span>{{ $purchase->supplier_name ?: '-' }}</span>
        </div>
        <div class="info-item">
            <label>Contact</label>
            <span>{{ $purchase->supplier_contact ?: '-' }}</span>
        </div>
        <div class="info-item">
            <label>Payment Status</label>
            <span class="badge badge-{{ $purchase->payment_status }}">{{ $purchase->payment_status }}</span>
        </div>
        <div class="info-item">
            <label>Status</label>
            <span class="badge badge-{{ $purchase->status }}">{{ $purchase->status }}</span>
        </div>
        <div class="info-item">
            <label>Amount Paid</label>
            <span>{{ $currencySymbol }}{{ number_format($purchase->amount_paid, 2) }}</span>
        </div>
        <div class="info-item">
            <label>Payment Type</label>
            <span>{{ $purchase->payment_type ?: '-' }}</span>
        </div>
        <div class="info-item">
            <label>Payment Type</label>
            <span>{{ $purchase->payment_type ?: '-' }}</span>
        </div>
    </div>
    @if ($purchase->notes)
        <div class="info-item" style="margin-top:15px;">
            <label>Notes</label>
            <span>{{ $purchase->notes }}</span>
        </div>
    @endif
</div>

<div class="card">
    <h3>Items</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th></th>
                <th>Product</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>Cost Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->items as $item)
                <tr>
                    <td>@if($item->image)<img src="{{ $item->image }}">@endif</td>
                    <td>{{ $item->product_title }}{{ $item->variant_title ? ' - ' . $item->variant_title : '' }}</td>
                    <td>{{ $item->sku ?: '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($item->cost_price, 2) }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Totals</h3>
    <div class="totals-box">
        <div class="trow"><span>Subtotal</span><span>{{ $currencySymbol }}{{ number_format($purchase->subtotal, 2) }}</span></div>
        <div class="trow"><span>Discount</span><span>-{{ $currencySymbol }}{{ number_format($purchase->discount, 2) }}</span></div>
        <div class="trow"><span>Tax</span><span>+{{ $currencySymbol }}{{ number_format($purchase->tax, 2) }}</span></div>
        <div class="trow"><span>Shipping</span><span>+{{ $currencySymbol }}{{ number_format($purchase->shipping_cost, 2) }}</span></div>
        <div class="trow grand"><span>Grand Total</span><span>{{ $currencySymbol }}{{ number_format($purchase->total, 2) }}</span></div>
    </div>
</div>

<a href="{{ route('purchases.edit', $purchase->id) }}" class="btn">Edit Purchase</a>
@endsection
