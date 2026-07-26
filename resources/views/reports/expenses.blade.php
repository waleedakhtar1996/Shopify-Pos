@extends('layouts.app')

@section('title', 'Expense Report')

@section('content')
<style>
    .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px; }
    .stat-card { background:white; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .stat-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .stat-card .value { font-size:26px; font-weight:700; color:#222; }
    .chart-card { background:white; border-radius:8px; padding:25px; max-width:500px; }
</style>

<h1>Expense Report</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="label">Total Expenses</div><div class="value">{{ $currencySymbol }}{{ number_format($totalExpenses, 2) }}</div></div>
    <div class="stat-card"><div class="label">Uncategorized</div><div class="value">{{ $currencySymbol }}{{ number_format($uncategorized, 2) }}</div></div>
</div>

<div class="chart-card">
    <h3 style="margin-top:0;">Category Breakdown</h3>
    <canvas id="expenseChart"></canvas>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('expenseChart'), {
    type: 'pie',
    data: {
        labels: @json($categoryData->pluck('name')),
        datasets: [{
            data: @json($categoryData->pluck('expenses_sum_amount')),
            backgroundColor: @json($categoryData->pluck('color'))
        }]
    },
    options: { responsive: true }
});
</script>
@endsection
