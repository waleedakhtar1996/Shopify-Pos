<?php

namespace App\Http\Controllers;

use App\Models\StaffLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index()
    {
        $shop = Auth::user();

        $staffLogins = StaffLogin::where('shop_id', $shop->id)->latest()->get();

        return view('staff.index', compact('staffLogins'));
    }

    public function store(Request $request)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:staff_logins,username',
            'password' => 'required|string|min:4',
            'display_name' => 'nullable|string|max:255',
            'role' => 'required|in:admin,staff',
        ]);

        StaffLogin::create([
            'shop_id' => $shop->id,
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'display_name' => $validated['display_name'] ?? $validated['username'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff login created!');
    }

    public function update(Request $request, StaffLogin $staffLogin)
    {
        $shop = Auth::user();
        if ($staffLogin->shop_id !== $shop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:staff_logins,username,' . $staffLogin->id,
            'password' => 'nullable|string|min:4',
            'display_name' => 'nullable|string|max:255',
            'role' => 'required|in:admin,staff',
        ]);

        $staffLogin->username = $validated['username'];
        $staffLogin->display_name = $validated['display_name'] ?? $validated['username'];
        $staffLogin->role = $validated['role'];

        if (!empty($validated['password'])) {
            $staffLogin->password = bcrypt($validated['password']);
        }

        $staffLogin->save();

        return redirect()->route('staff.index')->with('success', 'Staff login updated!');
    }

    public function destroy(StaffLogin $staffLogin)
    {
        $shop = Auth::user();
        if ($staffLogin->shop_id !== $shop->id) {
            abort(403);
        }

        if (StaffLogin::where('shop_id', $shop->id)->count() <= 1) {
            return back()->with('error', 'You cannot delete the last staff login.');
        }

        $staffLogin->delete();

        return redirect()->route('staff.index')->with('success', 'Staff login deleted.');
    }
}
