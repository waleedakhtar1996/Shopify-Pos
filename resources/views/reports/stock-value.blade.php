@extends('layouts.app')

@section('title', 'Stock Value Report')

@section('content')
<style>
    .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px; }
    .stat-card { background:white; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .stat-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .stat-card .value { font-size:26px; font-weight:700; }
    table.rep-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.rep-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.rep-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
</style>

<h1>Stock Value Report</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="label">Total Stock Value</div><div class="value">{{ $currencySymbol }}{{ number_format($totalValue, 2) }}</div></div>
    <div class="stat-card"><div class="label">Total Units in Stock</div><div class="value">{{ $totalUnits }}</div></div>
</div>

<table class="rep-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Avg Price</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['title'] }}</td>
                <td>{{ $row['qty'] }}</td>
                <td>{{ $currencySymbol }}{{ number_format($row['avg_price'], 2) }}</td>
                <td>{{ $currencySymbol }}{{ number_format($row['value'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="text-align:center; padding:30px; color:#888;">No stock data.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
