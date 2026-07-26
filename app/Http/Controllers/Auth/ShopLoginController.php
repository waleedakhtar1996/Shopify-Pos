<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StaffLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ShopLoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Staff username/password login
        if ($request->filled('username') && $request->filled('password')) {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $staff = StaffLogin::where('username', $request->input('username'))->first();

            if ($staff && Hash::check($request->input('password'), $staff->password)) {
                $shop = User::find($staff->shop_id);

                if ($shop) {
                    Auth::login($shop);
                    session(['staff_id' => $staff->id, 'staff_display_name' => $staff->display_name]);
                    return redirect()->route('dashboard');
                }
            }

            return back()->withInput()->withErrors(['username' => 'Invalid username or password.']);
        }

        // Shopify domain login (existing OAuth flow)
        if ($request->filled('shop')) {
            $shopDomain = trim($request->input('shop'));

            if (!str_contains($shopDomain, '.myshopify.com')) {
                $shopDomain .= '.myshopify.com';
            }

            $shop = User::where('name', $shopDomain)->first();

            if ($shop && !empty($shop->password)) {
                Auth::login($shop);
                return redirect()->route('dashboard');
            }

            return redirect('/?shop=' . $shopDomain);
        }

        return back()->withErrors(['username' => 'Please enter your login details.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        return redirect()->route('login');
    }
}
