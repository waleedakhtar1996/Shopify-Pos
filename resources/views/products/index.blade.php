@extends('layouts.app')

@section('title', 'Products')

@section('content')
<style>
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
    .btn-secondary { background:#555; }
    .filter-bar { background:white; border-radius:8px; padding:15px 20px; margin-bottom:15px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .filter-bar input[type=text], .filter-bar select { padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
    .filter-bar input[type=text] { flex:1; min-width:180px; }
    table.products-table { width:100%; border-collapse: collapse; background: white; border-radius:8px; overflow:hidden; }
    table.products-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.products-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; vertical-align:middle; }
    .prod-img { width:48px; height:48px; object-fit:cover; border-radius:6px; border:1px solid #eee; background:#fafafa; }
    .prod-img-placeholder { width:48px; height:48px; border-radius:6px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#bbb; font-size:11px; }
    .badge { padding:3px 10px; border-radius:10px; font-size:12px; font-weight:600; }
    .badge-active { background:#d4edda; color:#155724; }
    .badge-draft { background:#eee; color:#666; }
    .badge-archived { background:#f8d7da; color:#721c24; }
    .stock-low { color:#c0392b; font-weight:600; }
    .action-link { padding:6px 12px; border-radius:4px; font-size:13px; text-decoration:none; margin-right:6px; display:inline-block; }
    .edit-link { background:#e8f0fe; color:#1a56db; }
    .delete-btn { background:#fdecea; color:#c0392b; border:none; cursor:pointer; }
    form.inline { display:inline; }
    .pagination-wrap { margin-top:20px; }
</style>

<div class="top-bar">
    <h1>Products</h1>
    <div>
        <span id="syncTimer" style="font-size:13px; color:#555; background:#f0f0f0; padding:8px 14px; border-radius:20px; margin-right:10px; display:inline-block; font-weight:500;"></span>
        <form method="POST" action="{{ route('products.sync') }}" class="inline">
            @csrf
            <button type="submit" class="btn btn-secondary">🔄 Sync from Shopify</button>
        </form>
        <a href="{{ route('products.create') }}" class="btn">+ Add Product</a>
    </div>
</div>

<form method="GET" action="{{ route('products.index') }}" class="filter-bar">
    <input type="text" name="search" placeholder="Search by title..." value="{{ request('search') }}">
    <select name="status">
        <option value="all">All Statuses</option>
        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
        <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
    </select>
    <select name="sort">
        <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Newest First</option>
        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
        <option value="title_asc" {{ request('sort') == 'title_asc' ? 'selected' : '' }}>Title A-Z</option>
        <option value="title_desc" {{ request('sort') == 'title_desc' ? 'selected' : '' }}>Title Z-A</option>
    </select>
    <button type="submit" class="btn">Filter</button>
    @if(request('search') || (request('status') && request('status') != 'all') || request('sort'))
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Clear</a>
    @endif
</form>

<table class="products-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Image</th>
            <th>Title</th>
            <th>Quantity</th>
            <th>Barcode</th>
            <th>Status</th>
            <th>Variants</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($products as $product)
            @php
                $firstImage = $product->images->first();
                $firstVariant = $product->variants->first();
                $totalQty = $product->variants->sum('inventory_quantity');
            @endphp
            <tr>
                <td>{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                <td>
                    @if ($firstImage)
                        <img src="{{ $firstImage->src }}" class="prod-img" alt="">
                    @else
                        <div class="prod-img-placeholder">No img</div>
                    @endif
                </td>
                <td>{{ $product->title }}</td>
                <td class="{{ $totalQty <= 5 ? 'stock-low' : '' }}">{{ $totalQty }}</td>
                <td>{{ $firstVariant->barcode ?? '-' }}</td>
                <td>
                    @php
                        $badgeClass = match($product->status) {
                            'active' => 'badge-active',
                            'draft' => 'badge-draft',
                            'archived' => 'badge-archived',
                            default => 'badge-draft',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ ucfirst($product->status) }}</span>
                </td>
                <td>{{ $product->variants->count() }}</td>
                <td>
                    <a href="{{ route('products.edit', $product->id) }}" class="action-link edit-link">Edit</a>
                    <form method="POST" action="{{ route('products.destroy', $product->id) }}" class="inline" onsubmit="return confirm('Delete this product?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-link delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center; padding:30px; color:#888;">No products found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrap">
    {{ $products->links("vendor.pagination.custom") }}
</div>

<script>
(function() {
    let remaining = {{ $secondsRemaining ?? 300 }};
    const freq = {{ $freqSeconds ?? 300 }};
    const timerEl = document.getElementById('syncTimer');

    function formatTime(s) {
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return m + ':' + sec.toString().padStart(2, '0');
    }

    function tick() {
        if (!timerEl) return;
        if (remaining <= 0) {
            timerEl.textContent = 'Syncing...';
            fetch("{{ route('products.sync.ajax') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                window.location.reload();
            })
            .catch(() => {
                remaining = freq;
            });
            return;
        }
        timerEl.textContent = 'Next auto-sync in ' + formatTime(remaining);
        remaining--;
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
@endsection
