@extends('layouts.app')

@section('title', 'Orders')

@section('content')
<style>
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
    .btn-secondary { background:#555; }
    .filter-bar { background:white; border-radius:8px; padding:15px 20px; margin-bottom:15px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .filter-bar input[type=text], .filter-bar select { padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
    .filter-bar input[type=text] { flex:1; min-width:180px; }
    table.orders-table { width:100%; border-collapse: collapse; background: white; border-radius:8px; overflow:hidden; }
    table.orders-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.orders-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; vertical-align:middle; }
    .badge { padding:3px 10px; border-radius:10px; font-size:12px; font-weight:600; text-transform:capitalize; }
    .badge-paid, .badge-fulfilled { background:#d4edda; color:#155724; }
    .badge-pending, .badge-partial { background:#fff3cd; color:#856404; }
    .badge-refunded, .badge-unfulfilled { background:#f8d7da; color:#721c24; }
    .badge-default { background:#eee; color:#666; }
    .order-link { color:#1a56db; text-decoration:none; font-weight:600; }
    form.inline { display:inline; }
    .pagination-wrap { margin-top:20px; }
</style>

<div class="top-bar">
    <h1>Orders</h1>
    <div>
        <span id="syncTimer" style="font-size:13px; color:#555; background:#f0f0f0; padding:8px 14px; border-radius:20px; margin-right:10px; display:inline-block; font-weight:500;"></span>
        <form method="POST" action="{{ route('sales.sync') }}" class="inline">
            @csrf
            <button type="submit" class="btn">🔄 Sync from Shopify</button>
        </form>
    </div>
</div>

<form method="GET" action="{{ route('sales.index') }}" class="filter-bar">
    <input type="text" name="search" placeholder="Search order #, customer..." value="{{ request('search') }}">
    <select name="financial_status">
        <option value="all">All Payment Statuses</option>
        <option value="paid" {{ request('financial_status') == 'paid' ? 'selected' : '' }}>Paid</option>
        <option value="pending" {{ request('financial_status') == 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="refunded" {{ request('financial_status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
    </select>
    <select name="fulfillment_status">
        <option value="all">All Fulfillment Statuses</option>
        <option value="fulfilled" {{ request('fulfillment_status') == 'fulfilled' ? 'selected' : '' }}>Fulfilled</option>
        <option value="unfulfilled" {{ request('fulfillment_status') == 'unfulfilled' ? 'selected' : '' }}>Unfulfilled</option>
        <option value="partial" {{ request('fulfillment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
    </select>
    <select name="sort">
        <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Newest First</option>
        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
        <option value="total_high" {{ request('sort') == 'total_high' ? 'selected' : '' }}>Total: High to Low</option>
        <option value="total_low" {{ request('sort') == 'total_low' ? 'selected' : '' }}>Total: Low to High</option>
    </select>
    <button type="submit" class="btn">Filter</button>
    @if(request('search') || (request('financial_status') && request('financial_status') != 'all') || (request('fulfillment_status') && request('fulfillment_status') != 'all') || request('sort'))
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Clear</a>
    @endif
</form>

<table class="orders-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Order</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Payment</th>
            <th>Financial Status</th>
            <th>Fulfillment</th>
            <th>Items</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($orders as $order)
            @php
                $finBadge = match($order->financial_status) {
                    'paid' => 'badge-paid',
                    'pending' => 'badge-pending',
                    'refunded', 'partially_refunded' => 'badge-refunded',
                    default => 'badge-default',
                };
                $fulBadge = match($order->fulfillment_status) {
                    'fulfilled' => 'badge-fulfilled',
                    'partial' => 'badge-partial',
                    default => 'badge-unfulfilled',
                };
            @endphp
            <tr>
                <td>{{ $loop->iteration + ($orders->currentPage() - 1) * $orders->perPage() }}</td>
                <td><a href="{{ route('sales.show', $order->id) }}" class="order-link">{{ $order->order_number }}</a></td>
                <td>{{ $order->shopify_created_at?->format('M d, Y') }}</td>
                <td>{{ $order->customer_name ?? $order->customer_email ?? '-' }}</td>
                <td>{{ $order->payment_method ?? '-' }}</td>
                <td><span class="badge {{ $finBadge }}">{{ $order->financial_status ?? 'unknown' }}</span></td>
                <td><span class="badge {{ $fulBadge }}">{{ $order->fulfillment_status ?? 'unfulfilled' }}</span></td>
                <td>{{ $order->items->sum('quantity') }}</td>
                <td>{{ $currencySymbol }}{{ number_format($order->total_price, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align:center; padding:30px; color:#888;">No orders found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrap">
    {{ $orders->links("vendor.pagination.custom") }}
</div>

<script>
(function() {
    let remaining = {{ $secondsRemaining ?? 300 }};
    const freq = {{ $freqSeconds ?? 300 }};
    const timerEl = document.getElementById("syncTimer");

    function formatTime(s) {
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return m + ":" + sec.toString().padStart(2, "0");
    }

    function tick() {
        if (!timerEl) return;
        if (remaining <= 0) {
            timerEl.textContent = "Syncing...";
            fetch("{{ route('sales.sync.ajax') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]")?.content || "",
                    "Accept": "application/json",
                },
            })
            .then(r => r.json())
            .then(data => {
                window.location.reload();
            })
            .catch(() => {
                remaining = freq;
            });
            return;
        }
        timerEl.textContent = "Next auto-sync in " + formatTime(remaining);
        remaining--;
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
@endsection
