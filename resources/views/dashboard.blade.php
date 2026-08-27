@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<style>
    .dash-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:25px; }
    .dash-card { background:white; border-radius:10px; padding:22px; box-shadow:0 1px 3px rgba(0,0,0,0.08); position:relative; }
    .dash-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .dash-card .value { font-size:28px; font-weight:700; color:#222; }
    .dash-card .view-all { position:absolute; top:22px; right:22px; font-size:12px; color:#008060; text-decoration:none; font-weight:600; }
    .dash-card.alert .value { color:#c0392b; }
    .section-title { font-size:18px; font-weight:700; margin:30px 0 15px; }
    .chart-row { display:grid; grid-template-columns: 2fr 1fr; gap:18px; margin-bottom:10px; }
    .chart-card { background:white; border-radius:10px; padding:22px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .chart-card h3 { margin-top:0; font-size:15px; }
    table.recent-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.recent-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.recent-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
    .badge { padding:3px 10px; border-radius:10px; font-size:12px; font-weight:600; text-transform:capitalize; }
    .badge-paid { background:#d4edda; color:#155724; }
    .badge-pending { background:#fff3cd; color:#856404; }
    .badge-default { background:#eee; color:#666; }
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    @media (max-width: 900px) { .chart-row { grid-template-columns: 1fr; } }
</style>

<div class="top-bar">
    <h1>Dashboard</h1>
</div>

<div class="dash-grid">
    <div class="dash-card">
        <a href="{{ route('products.index') }}" class="view-all">View All →</a>
        <div class="label">Total Products</div>
        <div class="value">{{ $totalProducts }}</div>
    </div>

    <div class="dash-card">
        <a href="{{ route('sales.index') }}" class="view-all">View All →</a>
        <div class="label">Total Orders</div>
        <div class="value">{{ $totalOrders }}</div>
    </div>

    <div class="dash-card">
        <a href="{{ route('reports.sales') }}" class="view-all">View All →</a>
        <div class="label">Total Sales</div>
        <div class="value">{{ $currencySymbol }}{{ number_format($totalSales, 2) }}</div>
    </div>

    <div class="dash-card">
        <a href="{{ route('sales.index') }}" class="view-all">View All →</a>
        <div class="label">Total Customers</div>
        <div class="value">{{ $totalCustomers }}</div>
    </div>

    <div class="dash-card">
        <a href="{{ route('expenses.index') }}" class="view-all">View All →</a>
        <div class="label">Expenses (This Month)</div>
        <div class="value">{{ $currencySymbol }}{{ number_format($monthExpenses, 2) }}</div>
    </div>

</div>

<div class="chart-row">
    <div class="chart-card">
        <h3>Sales Trend (Last 7 Days)</h3>
        <canvas id="salesTrendChart"></canvas>
    </div>
    <div class="chart-card">
        <h3>Product Status</h3>
        <canvas id="productStatusChart"></canvas>
    </div>
</div>

<div class="chart-row">
    <div class="chart-card">
        <h3>Expense Breakdown</h3>
        <canvas id="expenseBreakdownChart"></canvas>
    </div>
    <div class="chart-card">
        <h3>Quick Links</h3>
        <div style="display:flex; flex-direction:column; gap:10px; margin-top:10px;">
            <a href="{{ route('reports.pnl') }}" style="color:#1a56db; text-decoration:none; font-size:14px;">→ P&amp;L Report</a>
            <a href="{{ route('reports.stock-value') }}" style="color:#1a56db; text-decoration:none; font-size:14px;">→ Stock Value Report</a>
            <a href="{{ route('reports.fast-moving') }}" style="color:#1a56db; text-decoration:none; font-size:14px;">→ Fast Moving Products</a>
            <a href="{{ route('reports.payment-type') }}" style="color:#1a56db; text-decoration:none; font-size:14px;">→ Payment Type Report</a>
        </div>
    </div>
</div>

<div class="section-title">Recent Orders</div>
<table class="recent-table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($recentOrders as $order)
            @php
                $badge = match($order->financial_status) {
                    'paid' => 'badge-paid',
                    'pending' => 'badge-pending',
                    default => 'badge-default',
                };
            @endphp
            <tr>
                <td><a href="{{ route('sales.show', $order->id) }}" style="color:#1a56db; text-decoration:none; font-weight:600;">{{ $order->order_number }}</a></td>
                <td>{{ $order->shopify_created_at?->format('M d, Y') }}</td>
                <td>{{ $order->customer_name ?? '-' }}</td>
                <td><span class="badge {{ $badge }}">{{ $order->financial_status ?? 'unknown' }}</span></td>
                <td>{{ $currencySymbol }}{{ number_format($order->total_price, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center; padding:30px; color:#888;">No orders yet.</td></tr>
        @endforelse
    </tbody>
</table>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('salesTrendChart'), {
    type: 'line',
    data: {
        labels: @json($chartLabels),
        datasets: [{
            label: 'Sales',
            data: @json($chartTotals),
            borderColor: '#008060',
            backgroundColor: 'rgba(0,128,96,0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true }
});

new Chart(document.getElementById('productStatusChart'), {
    type: 'pie',
    data: {
        labels: ['Active', 'Draft', 'Archived', 'Unlisted'],
        datasets: [{
            data: [{{ $activeProducts }}, {{ $draftProducts }}, {{ $archivedProducts }}, {{ $unlistedProducts }}],
            backgroundColor: ['#155724', '#999', '#721c24', '#856404']
        }]
    },
    options: { responsive: true }
});

new Chart(document.getElementById('expenseBreakdownChart'), {
    type: 'pie',
    data: {
        labels: @json($expenseCategories->pluck('name')),
        datasets: [{
            data: @json($expenseCategories->pluck('expenses_sum_amount')),
            backgroundColor: @json($expenseCategories->pluck('color'))
        }]
    },
    options: { responsive: true }
});
</script>
@endsection
