@extends('layouts.app')

@section('title', 'Stock Value Report')

@section('content')
<style>
    .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px; }
    .stat-card { background:white; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .stat-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .stat-card .value { font-size:26px; font-weight:700; }
    .filter-bar { background:white; border-radius:8px; padding:15px 20px; margin-bottom:15px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .filter-bar input[type=text], .filter-bar select { padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
    .filter-bar input[type=text] { flex:1; min-width:200px; }
    .btn { padding:9px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
    .btn-secondary { background:#555; }
    table.rep-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.rep-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.rep-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
    .na { color:#bbb; }
</style>

<h1>Stock Value Report</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="label">Total Stock Value (Selling Price)</div><div class="value" style="color:#008060;">{{ $currencySymbol }}{{ number_format($totalValue, 2) }}</div></div>
    <div class="stat-card"><div class="label">Total Stock Value (Cost Price)</div><div class="value" style="color:#b8860b;">{{ $currencySymbol }}{{ number_format($totalCostValue, 2) }}</div></div>
    <div class="stat-card"><div class="label">Total Units in Stock</div><div class="value">{{ $totalUnits }}</div></div>
</div>

<form method="GET" action="{{ route('reports.stock-value') }}" class="filter-bar">
    <select name="collection_id">
        <option value="all">All Collections ({{ $collections->sum('products_count') }})</option>
        @foreach ($collections as $collection)
            <option value="{{ $collection->id }}" {{ request('collection_id') == $collection->id ? 'selected' : '' }}>
                {{ $collection->title }} ({{ $collection->products_count }})
            </option>
        @endforeach
    </select>
    <input type="text" name="search" placeholder="Search by product title or SKU..." value="{{ request('search') }}">
    <button type="submit" class="btn">Filter</button>
    @if(request('collection_id') || request('search'))
        <a href="{{ route('reports.stock-value') }}" class="btn btn-secondary">Clear</a>
    @endif
</form>

<table class="rep-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Variant</th>
            <th>SKU</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Cost Price</th>
            <th>Value (Selling)</th>
            <th>Value (Cost)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['title'] }}</td>
                <td>{{ $row['variant'] }}</td>
                <td>{{ $row['sku'] ?: '-' }}</td>
                <td>{{ $row['qty'] }}</td>
                <td>{{ $currencySymbol }}{{ number_format($row['price'], 2) }}</td>
                <td>
                    @if($row['cost'] !== null)
                        {{ $currencySymbol }}{{ number_format($row['cost'], 2) }}
                    @else
                        <span class="na">Not set</span>
                    @endif
                </td>
                <td>{{ $currencySymbol }}{{ number_format($row['value'], 2) }}</td>
                <td>
                    @if($row['cost_value'] !== null)
                        {{ $currencySymbol }}{{ number_format($row['cost_value'], 2) }}
                    @else
                        <span class="na">-</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center; padding:30px; color:#888;">No stock data.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
