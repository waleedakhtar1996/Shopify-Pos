@extends('layouts.app')

@section('title', 'P&L Report')

@section('content')
<style>
    .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px; }
    .stat-card { background:white; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .stat-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .stat-card .value { font-size:26px; font-weight:700; }
    .chart-card { background:white; border-radius:8px; padding:25px; }
</style>

<h1>Profit & Loss Report</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="label">Total Sales</div><div class="value" style="color:#155724;">{{ $currencySymbol }}{{ number_format($totalSales, 2) }}</div></div>
    <div class="stat-card"><div class="label">Total Expenses</div><div class="value" style="color:#c0392b;">{{ $currencySymbol }}{{ number_format($totalExpenses, 2) }}</div></div>
    <div class="stat-card"><div class="label">Net Profit</div><div class="value" style="color:{{ $profit >= 0 ? '#155724' : '#c0392b' }};">{{ $currencySymbol }}{{ number_format($profit, 2) }}</div></div>
</div>

<div class="chart-card">
    <h3 style="margin-top:0;">Monthly Sales vs Expenses (Last 6 Months)</h3>
    <canvas id="pnlChart"></canvas>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('pnlChart'), {
    type: 'bar',
    data: {
        labels: @json($months),
        datasets: [
            { label: 'Sales', data: @json($salesData), backgroundColor: '#008060' },
            { label: 'Expenses', data: @json($expenseData), backgroundColor: '#c0392b' }
        ]
    },
    options: { responsive: true }
});
</script>
@endsection
