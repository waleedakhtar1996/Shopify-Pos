@extends('layouts.app')

@section('title', 'Currency Settings')

@section('content')
<style>
    .card { background:white; border-radius:8px; padding:25px; max-width:600px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .field { margin-bottom:20px; }
    .field label { display:block; font-weight:600; font-size:14px; margin-bottom:6px; }
    .field select { width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box; background:white; }
    .submit-btn { padding:12px 30px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px; }
</style>

<h1>Currency Settings</h1>
<p style="color:#666; margin-bottom:20px;">Choose the currency symbol used across the entire app — reports, sales, expenses, and products.</p>

<form method="POST" action="{{ route('settings.currency.update') }}">
    @csrf
    <div class="card">
        <div class="field">
            <label>Currency Symbol</label>
            <select name="currency_symbol">
                @php
                    $currencies = [
                        '$' => 'USD ($)',
                        'Rs' => 'PKR (Rs)',
                        'د.إ' => 'AED (د.إ)',
                        '€' => 'EUR (€)',
                        '£' => 'GBP (£)',
                        '₹' => 'INR (₹)',
                        '﷼' => 'SAR (﷼)',
                        'C$' => 'CAD (C$)',
                        'A$' => 'AUD (A$)',
                    ];
                @endphp
                @foreach ($currencies as $symbol => $label)
                    <option value="{{ $symbol }}" {{ $shop->currency_symbol == $symbol ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="submit-btn">Save Currency</button>
    </div>
</form>
@endsection
