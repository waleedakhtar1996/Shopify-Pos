@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<style>
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
    .btn-secondary { background:#555; }
    .filter-bar { background:white; border-radius:8px; padding:15px 20px; margin-bottom:15px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .filter-bar input[type=text], .filter-bar select { padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
    .filter-bar input[type=text] { flex:1; min-width:180px; }
    table.customers-table { width:100%; border-collapse: collapse; background: white; border-radius:8px; overflow:hidden; }
    table.customers-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.customers-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
    .pagination-wrap { margin-top:20px; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h1 style="margin:0;">Customers</h1>
    <div>
        <span id="syncTimer" style="font-size:13px; color:#555; background:#f0f0f0; padding:8px 14px; border-radius:20px; margin-right:10px; display:inline-block; font-weight:500;"></span>
    </div>
</div>

<form method="GET" action="{{ route('sales.customers') }}" class="filter-bar">
    <input type="text" name="search" placeholder="Search by name or email..." value="{{ request('search') }}">
    <select name="sort">
        <option value="total_high" {{ request('sort', 'total_high') == 'total_high' ? 'selected' : '' }}>Total Spent: High to Low</option>
        <option value="total_low" {{ request('sort') == 'total_low' ? 'selected' : '' }}>Total Spent: Low to High</option>
        <option value="orders_high" {{ request('sort') == 'orders_high' ? 'selected' : '' }}>Most Orders</option>
        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
    </select>
    <button type="submit" class="btn">Filter</button>
    @if(request('search') || request('sort'))
        <a href="{{ route('sales.customers') }}" class="btn btn-secondary">Clear</a>
    @endif
</form>

<table class="customers-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Orders</th>
            <th>Total Spent</th>
            <th>City</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($customers as $customer)
            <tr>
                <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
                <td>{{ $customer->full_name ?: '-' }}</td>
                <td>{{ $customer->email ?? '-' }}</td>
                <td>{{ $customer->phone ?? '-' }}</td>
                <td>{{ $customer->orders_count }}</td>
                <td>{{ $currencySymbol }}{{ number_format($customer->total_spent, 2) }}</td>
                <td>{{ $customer->city ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center; padding:30px; color:#888;">No customers found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrap">
    {{ $customers->links("vendor.pagination.custom") }}
</div>

<script>
(function() {
    let remaining = {{ $secondsRemaining ?? 300 }};
    const freq = {{ $freqSeconds ?? 300 }};
    const timerEl = document.getElementById('syncTimer');

    function formatTime(s) {
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return m + ':' + sec.toString().padStart(2, '0');
    }

    function tick() {
        if (!timerEl) return;
        if (remaining <= 0) {
            timerEl.textContent = 'Syncing...';
            fetch("{{ route('sales.sync.ajax') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'Accept': 'application/json',
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
        timerEl.textContent = 'Next auto-sync in ' + formatTime(remaining);
        remaining--;
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
@endsection
