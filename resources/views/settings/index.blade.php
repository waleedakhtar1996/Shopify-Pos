@extends('layouts.app')

@section('title', 'Sync Settings')

@section('content')
<style>
    .card { background:white; border-radius:8px; padding:25px; margin-bottom:20px; max-width:600px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .field { margin-bottom:20px; }
    .field label { display:block; font-weight:600; font-size:14px; margin-bottom:6px; }
    .field select { width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box; background:white; }
    .field .hint { font-size:12px; color:#888; margin-top:4px; }
    .last-synced { font-size:12px; color:#888; margin-top:4px; }
    .submit-btn { padding:12px 30px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px; }
</style>

<h1>Sync Settings</h1>
<p style="color:#666; margin-bottom:20px;">Choose how often each type of data should automatically sync from Shopify.</p>

@php
    $options = [5 => '5 minutes', 10 => '10 minutes', 15 => '15 minutes', 20 => '20 minutes', 25 => '25 minutes', 30 => '30 minutes'];
@endphp

<form method="POST" action="{{ route('settings.update') }}">
    @csrf
    <div class="card">
        <div class="field">
            <label>Orders Sync Frequency</label>
            <select name="orders_sync_frequency">
                @foreach ($options as $val => $label)
                    <option value="{{ $val }}" {{ $shop->orders_sync_frequency == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="hint">How often new orders are fetched automatically.</div>
            @if ($shop->orders_last_synced_at)
                <div class="last-synced">Last synced: {{ $shop->orders_last_synced_at->diffForHumans() }}</div>
            @endif
        </div>

        <div class="field">
            <label>Customers Sync Frequency</label>
            <select name="customers_sync_frequency">
                @foreach ($options as $val => $label)
                    <option value="{{ $val }}" {{ $shop->customers_sync_frequency == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="hint">How often customer records are fetched automatically.</div>
            @if ($shop->customers_last_synced_at)
                <div class="last-synced">Last synced: {{ $shop->customers_last_synced_at->diffForHumans() }}</div>
            @endif
        </div>

        <div class="field">
            <label>Products Sync Frequency</label>
            <select name="products_sync_frequency">
                @foreach ($options as $val => $label)
                    <option value="{{ $val }}" {{ $shop->products_sync_frequency == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="hint">How often products & collections are fetched automatically.</div>
            @if ($shop->products_last_synced_at)
                <div class="last-synced">Last synced: {{ $shop->products_last_synced_at->diffForHumans() }}</div>
            @endif
        </div>

        <div class="field">
            <label>Returns Sync Frequency</label>
            <select name="returns_sync_frequency">
                @foreach ($options as $val => $label)
                    <option value="{{ $val }}" {{ $shop->returns_sync_frequency == $val ? "selected" : "" }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="hint">How often returned/refunded orders are refreshed.</div>
            @if ($shop->returns_last_synced_at)
                <div class="last-synced">Last synced: {{ $shop->returns_last_synced_at->diffForHumans() }}</div>
            @endif
        </div>

        <button type="submit" class="submit-btn">Save Settings</button>
    </div>
</form>
@endsection
