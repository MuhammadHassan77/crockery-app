<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSizeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Language switch (works for guests too — login page needs it)
Route::get('language/{locale}', LanguageController::class)->name('language.switch');

// Public invoice share (WhatsApp link)
Route::get('/invoice/{token}/view', [InvoiceController::class, 'publicView'])->name('invoice.public');

// Authenticated routes
Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Global search
    Route::get('search', SearchController::class)->name('search');

    // Categories (AJAX quick-create)
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');

    // Products & Sizes
    Route::resource('products', ProductController::class);
    Route::resource('products.sizes', ProductSizeController::class)->shallow();
    Route::post('sizes/{size}/set-price', [ProductSizeController::class, 'setPrice'])->name('sizes.set-price');
    Route::post('sizes/{size}/adjust-stock', [StockController::class, 'adjust'])->name('sizes.adjust-stock');
    Route::post('sizes/{size}/add-stock', [StockController::class, 'purchase'])->name('sizes.add-stock');

    // Customers
    Route::resource('customers', CustomerController::class);
    Route::get('customers/{customer}/balance', [CustomerController::class, 'balance'])->name('customers.balance');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/whatsapp', [InvoiceController::class, 'whatsapp'])->name('invoices.whatsapp');

    // Payments
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // Reports
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
    Route::get('reports/stock', [ReportController::class, 'stock'])->name('reports.stock');

    // Settings (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('settings',        [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings',        [SettingsController::class, 'update'])->name('settings.update');
        Route::get('settings/preview',[SettingsController::class, 'preview'])->name('settings.preview');
        Route::get('backup/download', BackupController::class)->name('backup.download');
    });
});
