@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
<style>
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
    .btn-secondary { background:#555; }
    .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:20px; }
    .stat-card { background:white; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .stat-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .stat-card .value { font-size:24px; font-weight:700; }
    .filter-bar { background:white; border-radius:8px; padding:15px 20px; margin-bottom:15px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .filter-bar input[type=text], .filter-bar select { padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
    .filter-bar input[type=text] { flex:1; min-width:180px; }
    table.purchases-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.purchases-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.purchases-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
    .badge { padding:3px 10px; border-radius:10px; font-size:12px; font-weight:600; text-transform:capitalize; }
    .badge-paid, .badge-received { background:#d4edda; color:#155724; }
    .badge-partial, .badge-pending { background:#fff3cd; color:#856404; }
    .badge-unpaid, .badge-cancelled { background:#f8d7da; color:#721c24; }
    .action-link { padding:6px 12px; border-radius:4px; font-size:13px; text-decoration:none; margin-right:6px; display:inline-block; border:none; cursor:pointer; }
    .view-link { background:#e2e3e5; color:#333; }
    .edit-link { background:#e8f0fe; color:#1a56db; }
    .delete-btn { background:#fdecea; color:#c0392b; }
    form.inline { display:inline; }
    .pagination-wrap { margin-top:20px; }
</style>

<div class="top-bar">
    <h1>Purchases</h1>
    <a href="{{ route('purchases.create') }}" class="btn">+ New Purchase</a>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="label">Total Purchase Value</div><div class="value" style="color:#008060;">{{ $currencySymbol }}{{ number_format($totalPurchaseValue, 2) }}</div></div>
    <div class="stat-card"><div class="label">Total Paid</div><div class="value" style="color:#155724;">{{ $currencySymbol }}{{ number_format($totalPaid, 2) }}</div></div>
    <div class="stat-card"><div class="label">Total Unpaid</div><div class="value" style="color:#c0392b;">{{ $currencySymbol }}{{ number_format($totalUnpaid, 2) }}</div></div>
</div>

<form method="GET" action="{{ route('purchases.index') }}" class="filter-bar">
    <input type="text" name="search" placeholder="Search PO # or supplier..." value="{{ request('search') }}">
    <select name="status">
        <option value="all">All Statuses</option>
        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
    </select>
    <select name="payment_status">
        <option value="all">All Payment Statuses</option>
        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
        <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
    </select>
    <select name="sort">
        <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Newest First</option>
        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
        <option value="total_high" {{ request('sort') == 'total_high' ? 'selected' : '' }}>Total: High to Low</option>
        <option value="total_low" {{ request('sort') == 'total_low' ? 'selected' : '' }}>Total: Low to High</option>
    </select>
    <button type="submit" class="btn">Filter</button>
    @if(request('search') || (request('status') && request('status') != 'all') || (request('payment_status') && request('payment_status') != 'all') || request('sort'))
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Clear</a>
    @endif
</form>

<table class="purchases-table">
    <thead>
        <tr>
            <th>#</th>
            <th>PO Number</th>
            <th>Supplier</th>
            <th>Date</th>
            <th>Items</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($purchases as $purchase)
            <tr>
                <td>{{ $loop->iteration + ($purchases->currentPage() - 1) * $purchases->perPage() }}</td>
                <td>{{ $purchase->purchase_number ?: '-' }}</td>
                <td>{{ $purchase->supplier_name ?: '-' }}</td>
                <td>{{ $purchase->purchase_date->format('M d, Y') }}</td>
                <td>{{ $purchase->items_count }}</td>
                <td>{{ $currencySymbol }}{{ number_format($purchase->total, 2) }}</td>
                <td><span class="badge badge-{{ $purchase->payment_status }}">{{ $purchase->payment_status }}</span></td>
                <td><span class="badge badge-{{ $purchase->status }}">{{ $purchase->status }}</span></td>
                <td>
                    <a href="{{ route('purchases.show', $purchase->id) }}" class="action-link view-link">View</a>
                    <a href="{{ route('purchases.edit', $purchase->id) }}" class="action-link edit-link">Edit</a>
                    <form method="POST" action="{{ route('purchases.destroy', $purchase->id) }}" class="inline" onsubmit="return confirm('Delete this purchase entry?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-link delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align:center; padding:30px; color:#888;">No purchase entries yet.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrap">
    {{ $purchases->links('vendor.pagination.custom') }}
</div>
@endsection
