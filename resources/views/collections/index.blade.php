@extends('layouts.app')

@section('title', 'Collections')

@section('content')
<style>
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
    .grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:20px; }
    .col-card { background:white; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .col-img { width:100%; height:150px; object-fit:cover; background:#f0f0f0; }
    .col-img-placeholder { width:100%; height:150px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#bbb; font-size:13px; }
    .col-body { padding:15px; }
    .col-title { font-weight:600; font-size:15px; margin-bottom:4px; }
    .col-count { font-size:13px; color:#888; margin-bottom:12px; }
    .col-actions { display:flex; gap:8px; }
    .action-link { flex:1; text-align:center; padding:8px; border-radius:4px; font-size:13px; text-decoration:none; }
    .edit-link { background:#e8f0fe; color:#1a56db; }
    .delete-btn { background:#fdecea; color:#c0392b; border:none; cursor:pointer; width:100%; }
    form.inline { display:inline; width:100%; }
</style>

<div class="top-bar">
    <h1>Collections</h1>
    <a href="{{ route('collections.create') }}" class="btn">+ Add Collection</a>
</div>

<div class="grid">
    @forelse ($collections as $collection)
        <div class="col-card">
            @if ($collection->image)
                <img src="{{ $collection->image }}" class="col-img" alt="">
            @else
                <div class="col-img-placeholder">No image</div>
            @endif
            <div class="col-body">
                <div class="col-title">{{ $collection->title }}</div>
                <div class="col-count">{{ $collection->products_count ?? 0 }} products</div>
                <div class="col-actions">
                    <a href="{{ route('collections.edit', $collection->id) }}" class="action-link edit-link">Edit</a>
                    <form method="POST" action="{{ route('collections.destroy', $collection->id) }}" class="inline" onsubmit="return confirm('Delete this collection?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-link delete-btn">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <p style="color:#888;">No collections yet. Create your first one!</p>
    @endforelse
</div>
@endsection
