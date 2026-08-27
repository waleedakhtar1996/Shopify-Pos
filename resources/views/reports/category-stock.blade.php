@extends('layouts.app')

@section('title', 'Category-wise Stock')

@section('content')
<style>
    .chart-card { background:white; border-radius:8px; padding:25px; margin-bottom:20px; max-width:600px; }
    table.rep-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.rep-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.rep-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
</style>

<h1>Category-wise Stock</h1>



<div class="chart-card">
    <canvas id="catChart"></canvas>
</div>

<table class="rep-table">
    <thead>
        <tr>
            <th>Category / Type</th>
            <th>Stock Quantity</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($grouped as $category => $qty)
            <tr>
                <td>{{ $category }}</td>
                <td>{{ $qty }}</td>
            </tr>
        @empty
            <tr><td colspan="2" style="text-align:center; padding:30px; color:#888;">No stock data.</td></tr>
        @endforelse
    </tbody>
</table>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('catChart'), {
    type: 'pie',
    data: {
        labels: @json($grouped->keys()),
        datasets: [{
            data: @json($grouped->values()),
            backgroundColor: ['#008060','#1a56db','#c0392b','#856404','#6f42c1','#20c997','#e83e8c','#fd7e14']
        }]
    },
    options: { responsive: true }
});
</script>
@endsection
