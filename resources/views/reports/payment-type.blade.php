@extends('layouts.app')

@section('title', 'Payment Type Report')

@section('content')
<style>
    .chart-card { background:white; border-radius:8px; padding:25px; margin-bottom:20px; max-width:600px; }
    table.rep-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.rep-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.rep-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
</style>

<h1>Payment Type Report</h1>

@include('reports.partials.date-filter')

<p style="color:#666;">Sales, expenses, and purchases grouped by payment method (includes Shopify POS custom gateways).</p>

<div class="chart-card">
    <canvas id="paymentChart"></canvas>
</div>

<table class="rep-table">
    <thead>
        <tr>
            <th>Payment Method</th>
            <th>Orders</th>
            <th>Total Sales</th>
            <th>Total Expenses</th>
            <th>Total Purchases</th>
            <th>Remaining</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data as $row)
            <tr>
                <td>{{ $row->display_method }}</td>
                <td>{{ $row->cnt }}</td>
                <td>{{ $currencySymbol }}{{ number_format($row->total, 2) }}</td>
                <td style="color:#c0392b;">{{ $currencySymbol }}{{ number_format($row->expense_total, 2) }}</td>
                <td style="color:#c0392b;">{{ $currencySymbol }}{{ number_format($row->purchase_total, 2) }}</td>
                <td style="color:{{ $row->remaining >= 0 ? '#155724' : '#c0392b' }}; font-weight:600;">{{ $currencySymbol }}{{ number_format($row->remaining, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center; padding:30px; color:#888;">No payment data yet.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="font-weight:600; background:#fafafa;">
            <td>Total</td>
            <td>{{ $data->sum('cnt') }}</td>
            <td>{{ $currencySymbol }}{{ number_format($totalSales, 2) }}</td>
            <td style="color:#c0392b;">{{ $currencySymbol }}{{ number_format($totalExpensesForPaymentTypes, 2) }}</td>
            <td style="color:#c0392b;">{{ $currencySymbol }}{{ number_format($totalPurchasesForPaymentTypes, 2) }}</td>
            <td style="color:{{ $totalRemaining >= 0 ? '#155724' : '#c0392b' }};">{{ $currencySymbol }}{{ number_format($totalRemaining, 2) }}</td>
        </tr>
    </tfoot>
</table>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('paymentChart'), {
    type: 'pie',
    data: {
        labels: @json($data->pluck('display_method')),
        datasets: [{
            data: @json($data->pluck('total')),
            backgroundColor: ['#008060','#1a56db','#c0392b','#856404','#6f42c1','#20c997','#e83e8c','#fd7e14']
        }]
    },
    options: { responsive: true }
});
</script>
@endsection
