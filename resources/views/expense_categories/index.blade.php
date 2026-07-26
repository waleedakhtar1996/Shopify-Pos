@extends('layouts.app')

@section('title', 'Expense Categories')

@section('content')
<style>
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
    .card { background:white; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .field { margin-bottom:12px; }
    .field label { display:block; font-weight:600; font-size:13px; margin-bottom:5px; }
    .field input[type=text] { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box; }
    .field input[type=color] { width:60px; height:36px; border:1px solid #ccc; border-radius:4px; cursor:pointer; padding:2px; }
    .row { display:flex; gap:12px; align-items:flex-end; }
    table.cat-table { width:100%; border-collapse: collapse; background: white; border-radius:8px; overflow:hidden; }
    table.cat-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.cat-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; vertical-align:middle; }
    .color-dot { width:16px; height:16px; border-radius:50%; display:inline-block; margin-right:8px; vertical-align:middle; }
    .action-link { padding:6px 12px; border-radius:4px; font-size:13px; text-decoration:none; margin-right:6px; display:inline-block; border:none; cursor:pointer; }
    .edit-link { background:#e8f0fe; color:#1a56db; }
    .delete-btn { background:#fdecea; color:#c0392b; }
    form.inline { display:inline; }
</style>

<h1>Expense Categories</h1>

@if ($errors->any())
    <div class="error">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <h3 style="margin-top:0;">Add Category</h3>
    <form method="POST" action="{{ route('expense-categories.store') }}">
        @csrf
        <div class="row">
            <div class="field" style="flex:1;">
                <label>Name</label>
                <input type="text" name="name" placeholder="e.g. Rent, Utilities, Marketing" required>
            </div>
            <div class="field">
                <label>Color</label>
                <input type="color" name="color" value="#008060">
            </div>
            <div class="field">
                <button type="submit" class="btn">+ Add</button>
            </div>
        </div>
    </form>
</div>

<table class="cat-table">
    <thead>
        <tr>
            <th>Category</th>
            <th>Expenses Count</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($categories as $cat)
            <tr>
                <td><span class="color-dot" style="background:{{ $cat->color }};"></span>{{ $cat->name }}</td>
                <td>{{ $cat->expenses_count }}</td>
                <td>
                    <button type="button" class="action-link edit-link" onclick="document.getElementById('editForm{{ $cat->id }}').style.display = document.getElementById('editForm{{ $cat->id }}').style.display === 'none' ? 'block' : 'none';">Edit</button>
                    <form method="POST" action="{{ route('expense-categories.destroy', $cat->id) }}" class="inline" onsubmit="return confirm('Delete this category? Expenses will remain but uncategorized.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-link delete-btn">Delete</button>
                    </form>

                    <div id="editForm{{ $cat->id }}" style="display:none; margin-top:10px;">
                        <form method="POST" action="{{ route('expense-categories.update', $cat->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="field" style="flex:1;">
                                    <input type="text" name="name" value="{{ $cat->name }}" required>
                                </div>
                                <div class="field">
                                    <input type="color" name="color" value="{{ $cat->color }}">
                                </div>
                                <div class="field">
                                    <button type="submit" class="btn">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" style="text-align:center; padding:30px; color:#888;">No categories yet. Add your first one above.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
