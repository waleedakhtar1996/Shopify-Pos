<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Collection;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer("*", function ($view) {
            if (Auth::check()) {
                $shop = Auth::user();
                $view->with([
                    "sidebarProductsCount" => Product::where("user_id", $shop->id)->count(),
                    "sidebarOrdersCount" => Order::where("user_id", $shop->id)->count(),
                    "sidebarCustomersCount" => Customer::where("user_id", $shop->id)->count(),
                    "sidebarCollectionsCount" => Collection::where("user_id", $shop->id)->count(),
                    "currencySymbol" => $shop->currency_symbol ?? "$",
                ]);
            }
        });
    }
}
