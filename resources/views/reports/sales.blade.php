@extends('layouts.app')

@section('title', 'Total Sales Report')

@section('content')
<style>
    .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px; }
    .stat-card { background:white; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .stat-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .stat-card .value { font-size:26px; font-weight:700; color:#222; }
    .chart-card { background:white; border-radius:8px; padding:25px; max-width:500px; }
</style>

<h1>Total Sales Report</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="label">Total Sales</div><div class="value">{{ $currencySymbol }}{{ number_format($totalSales, 2) }}</div></div>
    <div class="stat-card"><div class="label">Total Orders</div><div class="value">{{ $totalOrders }}</div></div>
    <div class="stat-card"><div class="label">Avg Order Value</div><div class="value">{{ $currencySymbol }}{{ number_format($avgOrderValue, 2) }}</div></div>
    <div class="stat-card"><div class="label">Paid Sales</div><div class="value" style="color:#155724;">{{ $currencySymbol }}{{ number_format($paidSales, 2) }}</div></div>
    <div class="stat-card"><div class="label">Pending Sales</div><div class="value" style="color:#856404;">{{ $currencySymbol }}{{ number_format($pendingSales, 2) }}</div></div>
</div>

<div class="chart-card">
    <h3 style="margin-top:0;">Paid vs Pending</h3>
    <canvas id="salesChart"></canvas>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('salesChart'), {
    type: 'pie',
    data: {
        labels: ['Paid', 'Pending', 'Other'],
        datasets: [{
            data: [{{ $paidSales }}, {{ $pendingSales }}, {{ max(0, $totalSales - $paidSales - $pendingSales) }}],
            backgroundColor: ['#155724', '#856404', '#999']
        }]
    },
    options: { responsive: true }
});
</script>
@endsection
