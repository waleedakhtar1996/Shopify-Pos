@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
<style>
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; display:inline-block; }
    .btn-secondary { background:#555; }
    .summary-card { background:white; border-radius:8px; padding:20px; margin-bottom:15px; }
    .summary-card .amount { font-size:28px; font-weight:700; color:#008060; }
    .filter-bar { background:white; border-radius:8px; padding:15px 20px; margin-bottom:15px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .filter-bar input[type=text], .filter-bar select { padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
    .filter-bar input[type=text] { flex:1; min-width:180px; }
    table.exp-table { width:100%; border-collapse: collapse; background: white; border-radius:8px; overflow:hidden; }
    table.exp-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.exp-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; vertical-align:middle; }
    .color-dot { width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:6px; }
    .action-link { padding:6px 12px; border-radius:4px; font-size:13px; text-decoration:none; margin-right:6px; display:inline-block; }
    .edit-link { background:#e8f0fe; color:#1a56db; }
    .delete-btn { background:#fdecea; color:#c0392b; border:none; cursor:pointer; }
    form.inline { display:inline; }
    .pagination-wrap { margin-top:20px; }
</style>

<div class="top-bar">
    <h1>Expenses</h1>
    <a href="{{ route('expenses.create') }}" class="btn">+ Add Expense</a>
</div>

<div class="summary-card">
    <div style="font-size:13px; color:#888;">Total Expenses</div>
    <div class="amount">{{ $currencySymbol }}{{ number_format($totalAmount, 2) }}</div>
</div>

<form method="GET" action="{{ route('expenses.index') }}" class="filter-bar">
    <input type="text" name="search" placeholder="Search by title..." value="{{ request('search') }}">
    <select name="category_id">
        <option value="all">All Categories</option>
        @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
        @endforeach
    </select>
    <select name="sort">
        <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Newest First</option>
        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
        <option value="amount_high" {{ request('sort') == 'amount_high' ? 'selected' : '' }}>Amount: High to Low</option>
        <option value="amount_low" {{ request('sort') == 'amount_low' ? 'selected' : '' }}>Amount: Low to High</option>
    </select>
    <button type="submit" class="btn">Filter</button>
    @if(request('search') || (request('category_id') && request('category_id') != 'all') || request('sort'))
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Clear</a>
    @endif
</form>

<table class="exp-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Title</th>
            <th>Category</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($expenses as $expense)
            <tr>
                <td>{{ $loop->iteration + ($expenses->currentPage() - 1) * $expenses->perPage() }}</td>
                <td>{{ $expense->title }}</td>
                <td>
                    @if ($expense->category)
                        <span class="color-dot" style="background:{{ $expense->category->color }};"></span>{{ $expense->category->name }}
                    @else
                        <span style="color:#999;">Uncategorized</span>
                    @endif
                </td>
                <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                <td>{{ $currencySymbol }}{{ number_format($expense->amount, 2) }}</td>
                <td>
                    <a href="{{ route('expenses.edit', $expense->id) }}" class="action-link edit-link">Edit</a>
                    <form method="POST" action="{{ route('expenses.destroy', $expense->id) }}" class="inline" onsubmit="return confirm('Delete this expense?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-link delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center; padding:30px; color:#888;">No expenses found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrap">
    {{ $expenses->links('vendor.pagination.custom') }}
</div>
@endsection
