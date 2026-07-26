@extends('layouts.app')

@section('title', 'Barcode Inventory')

@section('content')
<style>
    .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px; }
    .stat-card { background:white; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .stat-card .label { font-size:13px; color:#888; margin-bottom:6px; }
    .stat-card .value { font-size:26px; font-weight:700; }
    .filter-bar { background:white; border-radius:8px; padding:15px 20px; margin-bottom:15px; display:flex; gap:12px; }
    .filter-bar input[type=text] { flex:1; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:14px; }
    table.rep-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.rep-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.rep-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
    .pagination-wrap { margin-top:20px; }
</style>

<h1>Barcode Inventory</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="label">Products with Barcode</div><div class="value">{{ $withBarcodeCount }}</div></div>
    <div class="stat-card"><div class="label">Total Products</div><div class="value">{{ $totalProducts }}</div></div>
</div>

<form method="GET" class="filter-bar">
    <input type="text" name="search" placeholder="Search by title..." value="{{ request('search') }}">
    <button type="submit" class="btn">Search</button>
</form>

<table class="rep-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Variant</th>
            <th>Barcode</th>
            <th>Stock</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($products as $product)
            @foreach ($product->variants as $variant)
                <tr>
                    <td>{{ $product->title }}</td>
                    <td>{{ trim(($variant->option1 ?? '') . ' ' . ($variant->option2 ?? '') . ' ' . ($variant->option3 ?? '')) ?: '-' }}</td>
                    <td>{{ $variant->barcode }}</td>
                    <td>{{ $variant->inventory_quantity }}</td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="4" style="text-align:center; padding:30px; color:#888;">No products with barcodes found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrap">
    {{ $products->links('vendor.pagination.custom') }}
</div>
@endsection
