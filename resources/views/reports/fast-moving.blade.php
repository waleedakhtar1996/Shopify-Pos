@extends('layouts.app')

@section('title', 'Fast Moving Products')

@section('content')
<style>
    table.rep-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.rep-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.rep-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
    .chart-card { background:white; border-radius:8px; padding:25px; margin-bottom:20px; }
</style>

<h1>Top 10 Fast Moving Products</h1>

@include('reports.partials.date-filter')

<p style="color:#666;">Best-selling products by quantity.</p>

<div class="chart-card">
    <canvas id="fastChart"></canvas>
</div>

<table class="rep-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Units Sold</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($sold as $item)
            <tr>
                <td>{{ $item->title }}</td>
                <td>{{ $item->total_qty }}</td>
            </tr>
        @empty
            <tr><td colspan="2" style="text-align:center; padding:30px; color:#888;">No sales data yet.</td></tr>
        @endforelse
    </tbody>
</table>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('fastChart'), {
    type: 'bar',
    data: {
        labels: @json($sold->pluck('title')),
        datasets: [{
            label: 'Units Sold',
            data: @json($sold->pluck('total_qty')),
            backgroundColor: '#008060'
        }]
    },
    options: { responsive: true, indexAxis: 'y' }
});
</script>
@endsection
