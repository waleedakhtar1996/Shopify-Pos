@extends('layouts.app')

@section('title', 'Slow Moving Products')

@section('content')
<style>
    table.rep-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.rep-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.rep-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
    .badge-never { background:#fdecea; color:#c0392b; padding:3px 10px; border-radius:10px; font-size:12px; }
</style>

<h1>Top 10 Slow Moving Products</h1>

@include('reports.partials.date-filter')

<p style="color:#666;">Products with the lowest sales quantity.</p>

<table class="rep-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Units Sold</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sold as $item)
            <tr>
                <td>{{ $item->title }}</td>
                <td>{{ $item->total_qty }}</td>
            </tr>
        @endforeach
        @foreach ($neverSold as $title)
            <tr>
                <td>{{ $title }}</td>
                <td><span class="badge-never">Never sold</span></td>
            </tr>
        @endforeach
        @if ($sold->isEmpty() && $neverSold->isEmpty())
            <tr><td colspan="2" style="text-align:center; padding:30px; color:#888;">No data yet.</td></tr>
        @endif
    </tbody>
</table>
@endsection
