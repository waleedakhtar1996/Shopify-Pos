@extends('layouts.app')

@section('title', 'Staff Logins')

@section('content')
<style>
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
    .btn { padding:10px 20px; background:#008060; color:white; border:none; border-radius:4px; cursor:pointer; text-decoration:none; font-size:14px; }
    .card { background:white; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .field { margin-bottom:14px; }
    .field label { display:block; font-weight:600; font-size:13px; margin-bottom:5px; }
    .field input, .field select { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px; box-sizing:border-box; }
    .row { display:flex; gap:12px; flex-wrap:wrap; }
    .row > div { flex:1; min-width:180px; }
    table.staff-table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    table.staff-table th { text-align:left; padding:12px; background:#f5f5f5; font-size:13px; color:#555; }
    table.staff-table td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
    .badge { padding:3px 10px; border-radius:10px; font-size:12px; font-weight:600; text-transform:capitalize; }
    .badge-admin { background:#d4edda; color:#155724; }
    .badge-staff { background:#eee; color:#666; }
    .action-link { padding:6px 12px; border-radius:4px; font-size:13px; text-decoration:none; margin-right:6px; display:inline-block; border:none; cursor:pointer; }
    .edit-link { background:#e8f0fe; color:#1a56db; }
    .delete-btn { background:#fdecea; color:#c0392b; }
    form.inline { display:inline; }
    .edit-row { display:none; }
</style>

<div class="top-bar">
    <h1>Staff Logins</h1>
</div>
<p style="color:#666; margin-top:-10px; margin-bottom:20px;">Create login accounts for your team. Everyone logs into the same shop data.</p>

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
    <h3 style="margin-top:0;">Add New Staff Login</h3>
    <form method="POST" action="{{ route('staff.store') }}">
        @csrf
        <div class="row">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="field">
                <label>Display Name</label>
                <input type="text" name="display_name" placeholder="e.g. John">
            </div>
            <div class="field">
                <label>Role</label>
                <select name="role">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn">+ Add Staff Login</button>
    </form>
</div>

<table class="staff-table">
    <thead>
        <tr>
            <th>Username</th>
            <th>Display Name</th>
            <th>Role</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($staffLogins as $staff)
            <tr>
                <td>{{ $staff->username }}</td>
                <td>{{ $staff->display_name }}</td>
                <td><span class="badge {{ $staff->role == 'admin' ? 'badge-admin' : 'badge-staff' }}">{{ $staff->role }}</span></td>
                <td>{{ $staff->created_at->format('M d, Y') }}</td>
                <td>
                    <button type="button" class="action-link edit-link" onclick="document.getElementById('edit{{ $staff->id }}').style.display = document.getElementById('edit{{ $staff->id }}').style.display === 'none' ? 'table-row' : 'none';">Edit</button>
                    <form method="POST" action="{{ route('staff.destroy', $staff->id) }}" class="inline" onsubmit="return confirm('Delete this staff login?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-link delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <tr class="edit-row" id="edit{{ $staff->id }}">
                <td colspan="5">
                    <form method="POST" action="{{ route('staff.update', $staff->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="field">
                                <label>Username</label>
                                <input type="text" name="username" value="{{ $staff->username }}" required>
                            </div>
                            <div class="field">
                                <label>New Password</label>
                                <input type="password" name="password" placeholder="Leave blank to keep current">
                            </div>
                            <div class="field">
                                <label>Display Name</label>
                                <input type="text" name="display_name" value="{{ $staff->display_name }}">
                            </div>
                            <div class="field">
                                <label>Role</label>
                                <select name="role">
                                    <option value="staff" {{ $staff->role == 'staff' ? 'selected' : '' }}>Staff</option>
                                    <option value="admin" {{ $staff->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn">Save Changes</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center; padding:30px; color:#888;">No staff logins yet.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
