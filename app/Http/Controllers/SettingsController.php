<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $shop = Auth::user();

        return view('settings.index', compact('shop'));
    }

    public function update(Request $request)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'orders_sync_frequency' => 'required|integer|in:5,10,15,20,25,30',
            'returns_sync_frequency' => 'required|integer|in:5,10,15,20,25,30',
            'customers_sync_frequency' => 'required|integer|in:5,10,15,20,25,30',
            'products_sync_frequency' => 'required|integer|in:5,10,15,20,25,30',
        ]);

        $shop->orders_sync_frequency = $validated['orders_sync_frequency'];
        $shop->returns_sync_frequency = $validated['returns_sync_frequency'];
        $shop->customers_sync_frequency = $validated['customers_sync_frequency'];
        $shop->products_sync_frequency = $validated['products_sync_frequency'];
        $shop->save();

        return redirect()->route('settings.index')->with('success', 'Sync settings updated!');
    }

    public function currency()
    {
        $shop = Auth::user();
        return view('settings.currency', compact('shop'));
    }

    public function updateCurrency(Request $request)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'currency_symbol' => 'required|string|max:10',
        ]);

        $shop->currency_symbol = $validated['currency_symbol'];
        $shop->save();

        return redirect()->route('settings.currency')->with('success', 'Currency updated!');
    }

    public function appName()
    {
        $shop = Auth::user();
        return view('settings.app-name', compact('shop'));
    }

    public function updateAppName(Request $request)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'app_display_name' => 'required|string|max:255',
        ]);

        $shop->app_display_name = $validated['app_display_name'];
        $shop->save();

        return redirect()->route('settings.app-name')->with('success', 'App name updated!');
    }
}
