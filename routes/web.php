<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\Auth\ShopLoginController;

Route::get('/login', [ShopLoginController::class, 'show'])->name('login');
Route::post('/login', [ShopLoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [ShopLoginController::class, 'logout'])->name('logout');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');

    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/search-products', [PurchaseController::class, 'searchProducts'])->name('purchases.search-products');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
    Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');

    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::put('/staff/{staffLogin}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staffLogin}', [StaffController::class, 'destroy'])->name('staff.destroy');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/sync', [ProductController::class, 'sync'])->name('products.sync');
    Route::post('/products/sync-ajax', [ProductController::class, 'syncAjax'])->name('products.sync.ajax');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::post('/collections/sync', [CollectionController::class, 'sync'])->name('collections.sync');
    Route::post('/collections/sync-ajax', [CollectionController::class, 'syncAjax'])->name('collections.sync.ajax');
    Route::get('/collections/sync-list', [CollectionController::class, 'listForSync'])->name('collections.sync.list');
    Route::post('/collections/sync-one/{id}', [CollectionController::class, 'syncOne'])->name('collections.sync.one');
    Route::post('/collections/mark-synced', [CollectionController::class, 'markSynced'])->name('collections.mark.synced');
    Route::get('/collections/create', [CollectionController::class, 'create'])->name('collections.create');
    Route::post('/collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::get('/collections/{collection}/edit', [CollectionController::class, 'edit'])->name('collections.edit');
    Route::put('/collections/{collection}', [CollectionController::class, 'update'])->name('collections.update');
    Route::delete('/collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');

    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::get('/sales/{order}', [SalesController::class, 'show'])->name('sales.show');
    Route::post('/sales/sync', [SalesController::class, 'sync'])->name('sales.sync');
    Route::post('/sales/sync-ajax', [SalesController::class, 'syncAjax'])->name('sales.sync.ajax');
    Route::get('/customers', [SalesController::class, 'customers'])->name('sales.customers');
    Route::post('/customers/sync', [SalesController::class, 'syncCustomersOnly'])->name('sales.customers.sync');
    Route::get('/customers', [SalesController::class, 'customers'])->name('sales.customers');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/currency', [SettingsController::class, 'currency'])->name('settings.currency');
    Route::post('/settings/currency', [SettingsController::class, 'updateCurrency'])->name('settings.currency.update');
    Route::get('/settings/app-name', [SettingsController::class, 'appName'])->name('settings.app-name');
    Route::post('/settings/app-name', [SettingsController::class, 'updateAppName'])->name('settings.app-name.update');

    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
    Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
    Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('/reports/products', [ReportController::class, 'products'])->name('reports.products');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales'])->name('reports.daily-sales');
    Route::get('/reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
    Route::get('/reports/slow-moving', [ReportController::class, 'slowMoving'])->name('reports.slow-moving');
    Route::get('/reports/fast-moving', [ReportController::class, 'fastMoving'])->name('reports.fast-moving');
    Route::get('/reports/pnl', [ReportController::class, 'pnl'])->name('reports.pnl');
    Route::get('/reports/stock-value', [ReportController::class, 'stockValue'])->name('reports.stock-value');
    Route::get('/reports/category-stock', [ReportController::class, 'categoryStock'])->name('reports.category-stock');
    Route::get('/reports/barcode-inventory', [ReportController::class, 'barcodeInventory'])->name('reports.barcode-inventory');
    Route::get('/reports/payment-type', [ReportController::class, 'paymentType'])->name('reports.payment-type');
    Route::get('/reports/returns', [ReportController::class, 'returns'])->name('reports.returns');
    Route::post('/reports/returns/sync-ajax', [ReportController::class, 'returnsSyncAjax'])->name('reports.returns.sync.ajax');
});
