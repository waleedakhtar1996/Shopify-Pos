@extends('layouts.app')

@section('title', 'Product Returns Report')

@section('content')
<style>
    .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px; }
    .stat-card { background:white; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .stat-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .stat-card .value { font-size:26px; font-weight:700; }
    table.rep-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.rep-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.rep-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
    .badge { padding:3px 10px; border-radius:10px; font-size:12px; font-weight:600; text-transform:capitalize; }
    .badge-refunded { background:#f8d7da; color:#721c24; }
    .pagination-wrap { margin-top:20px; }
</style>

<h1>Product Returns Report</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="label">Total Returned Orders</div><div class="value">{{ $totalReturns }}</div></div>
    <div class="stat-card"><div class="label">Total Returned Value</div><div class="value" style="color:#721c24;">{{ $currencySymbol }}{{ number_format($totalReturnedValue, 2) }}</div></div>
</div>

<table class="rep-table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Date</th>
            <th>Status</th>
            <th>Items</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($returns as $order)
            <tr>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->shopify_created_at?->format('M d, Y') }}</td>
                <td><span class="badge badge-refunded">{{ str_replace('_', ' ', $order->financial_status) }}</span></td>
                <td>{{ $order->items->sum('quantity') }}</td>
                <td>{{ $currencySymbol }}{{ number_format($order->total_price, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center; padding:30px; color:#888;">No returns found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrap">
    {{ $returns->links('vendor.pagination.custom') }}
</div>
@endsection
