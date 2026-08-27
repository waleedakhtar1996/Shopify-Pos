@extends('layouts.app')

@section('title', 'Add Expense')

@section('content')
<style>
    .card { background:white; border-radius:8px; padding:20px; margin-bottom:20px; max-width:600px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .field { margin-bottom:15px; }
    .field label { display:block; font-weight:600; font-size:13px; margin-bottom:5px; }
    .field input[type=text], .field input[type=number], .field input[type=date], .field select, .field textarea {
        width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box;
    }
    .row { display:flex; gap:15px; }
    .row > div { flex:1; }
    .submit-btn { padding:12px 30px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px; }
    .back-link { color:#1a56db; text-decoration:none; font-size:14px; }
</style>

<a href="{{ route('expenses.index') }}" class="back-link">&larr; Back to Expenses</a>
<h1>Add Expense</h1>

@if ($errors->any())
    <div class="error">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('expenses.store') }}">
    @csrf
    <div class="card">
        <div class="field">
            <label>Title</label>
            <input type="text" name="title" value="{{ old('title') }}" required>
        </div>
        <div class="row">
            <div class="field">
                <label>Category</label>
                <select name="expense_category_id">
                    <option value="">None</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Amount * ({{ $currencySymbol }})</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required>
            </div>
        </div>
        <div class="field">
            <label>Payment Type</label>
            <select name="payment_type">
                <option value="">Select payment type</option>
                @foreach ($paymentTypes as $type)
                    <option value="{{ $type }}" {{ old('payment_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Date *</label>
            <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required>
        </div>
        <div class="field">
            <label>Notes</label>
            <textarea name="notes" rows="3">{{ old('notes') }}</textarea>
        </div>
    </div>
    <button type="submit" class="submit-btn">Save Expense</button>
</form>
@endsection
