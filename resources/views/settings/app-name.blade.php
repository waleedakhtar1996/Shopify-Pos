@extends('layouts.app')

@section('title', 'App Name Settings')

@section('content')
<style>
    .card { background:white; border-radius:8px; padding:25px; max-width:500px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .field { margin-bottom:20px; }
    .field label { display:block; font-weight:600; font-size:14px; margin-bottom:6px; }
    .field input { width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box; }
    .field .hint { font-size:12px; color:#888; margin-top:5px; }
    .submit-btn { padding:12px 30px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px; }
</style>

<h1>App Name</h1>
<p style="color:#666; margin-bottom:20px;">This name appears in the top-left of the header, across the entire app.</p>

@if ($errors->any())
    <div class="error">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('settings.app-name.update') }}">
    @csrf
    <div class="card">
        <div class="field">
            <label>App Display Name</label>
            <input type="text" name="app_display_name" value="{{ old('app_display_name', $shop->app_display_name) }}" placeholder="e.g. Adxsway POS" required>
            <div class="hint">Leave blank to show your Shopify store name instead.</div>
        </div>
        <button type="submit" class="submit-btn">Save</button>
    </div>
</form>
@endsection
