@extends('layouts.app')

@section('title', 'Daily Sales Report')

@section('content')
<style>
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .filter-bar select { padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
    .chart-card { background:white; border-radius:8px; padding:25px; }
    table.daily-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; margin-top:20px; }
    table.daily-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.daily-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
</style>

<div class="top-bar">
    <h1>Daily Sales Report</h1>


    <form method="GET">
        <select name="days" onchange="this.form.submit()">
            <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
            <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
            <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
        </select>
    </form>
</div>

<div class="chart-card">
    <canvas id="dailyChart"></canvas>
</div>

<table class="daily-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Orders</th>
            <th>Sales</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($labels as $i => $label)
            <tr>
                <td>{{ $label }}</td>
                <td>{{ $counts[$i] }}</td>
                <td>{{ $currencySymbol }}{{ number_format($totals[$i], 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: @json($labels),
        datasets: [{
            label: 'Sales',
            data: @json($totals),
            borderColor: '#008060',
            backgroundColor: 'rgba(0,128,96,0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true }
});
</script>
@endsection
