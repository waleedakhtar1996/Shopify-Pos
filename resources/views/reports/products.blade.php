@extends('layouts.app')

@section('title', 'Total Products Report')

@section('content')
<style>
    .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px; }
    .stat-card { background:white; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .stat-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .stat-card .value { font-size:26px; font-weight:700; color:#222; }
    .chart-card { background:white; border-radius:8px; padding:25px; max-width:500px; }
</style>

<h1>Total Products Report</h1>

@include('reports.partials.date-filter')


<div class="stat-grid">
    <div class="stat-card"><div class="label">Total Products</div><div class="value">{{ $total }}</div></div>
    <div class="stat-card"><div class="label">Active</div><div class="value" style="color:#155724;">{{ $active }}</div></div>
    <div class="stat-card"><div class="label">Draft</div><div class="value" style="color:#666;">{{ $draft }}</div></div>
    <div class="stat-card"><div class="label">Archived</div><div class="value" style="color:#721c24;">{{ $archived }}</div></div>
    <div class="stat-card"><div class="label">Unlisted</div><div class="value" style="color:#856404;">{{ $unlisted }}</div></div>
    <div class="stat-card"><div class="label">Total Variants</div><div class="value">{{ $totalVariants }}</div></div>
</div>

<div class="chart-card">
    <h3 style="margin-top:0;">Status Breakdown</h3>
    <canvas id="statusChart"></canvas>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: {
        labels: ['Active', 'Draft', 'Archived', 'Unlisted'],
        datasets: [{
            data: [{{ $active }}, {{ $draft }}, {{ $archived }}, {{ $unlisted }}],
            backgroundColor: ['#155724', '#999', '#721c24', '#856404']
        }]
    },
    options: { responsive: true }
});
</script>
@endsection
