@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<style>
    .card { background:white; border-radius:8px; padding:25px; max-width:500px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .field { margin-bottom:16px; }
    .field label { display:block; font-weight:600; font-size:13px; margin-bottom:5px; }
    .field input { width:100%; padding:9px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box; }
    .submit-btn { padding:12px 30px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px; }
    .divider { border-top:1px solid #eee; margin:20px 0; padding-top:5px; }
    .hint { font-size:12px; color:#888; margin-top:4px; }
</style>

<h1>Profile</h1>

@if ($errors->any())
    <div class="error">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    <div class="card">
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" value="{{ old('username', $shop->username) }}" required>
        </div>
        <div class="field">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $shop->email) }}">
        </div>

        <div class="divider">
            <p style="font-size:13px; color:#888; margin-bottom:10px;">Leave password fields empty if you don't want to change it.</p>
        </div>

        <div class="field">
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="Required only to change password">
        </div>
        <div class="field">
            <label>New Password</label>
            <input type="password" name="new_password">
        </div>
        <div class="field">
            <label>Confirm New Password</label>
            <input type="password" name="new_password_confirmation">
        </div>

        <button type="submit" class="submit-btn">Save Changes</button>
    </div>
</form>
@endsection
