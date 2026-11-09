<?php

use App\Http\Controllers\LogViewerController;
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
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\PurchasePaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

// Auth
Route::middleware('guest')->group(function () {
    // \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Language switch (works for guests too — login page needs it)
Route::get('language/{locale}', LanguageController::class)->name('language.switch');

// Public invoice share (WhatsApp link)
Route::get('/invoice/{token}/view', [InvoiceController::class, 'publicView'])->name('invoice.public');
Route::get('/purchase_invoice/{token}/view', [PurchaseInvoiceController::class, 'publicView'])->name('purchase_invoice.public');

Route::middleware(['auth', 'admin'])
    ->prefix('admin/database-backups')
    ->name('database-backups.')
    ->group(function () {

        Route::get('/', [BackupController::class, 'index'])
            ->name('index');

        Route::post('/', [BackupController::class, 'store'])
            ->name('store');

        Route::post('/restore', [BackupController::class, 'restore'])
            ->name('restore');

        Route::get('/download/{filename}', [BackupController::class, 'download'])
            ->where('filename', '.*')
            ->name('download');

        Route::delete('/{filename}', [BackupController::class, 'destroy'])
            ->where('filename', '.*')
            ->name('destroy');
    });
// Authenticated routes
Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Global search
    Route::get('search', SearchController::class)->name('search');

    // Select2 AJAX search endpoints
    Route::prefix('api/search')->group(function () {
        Route::get('customers', [CustomerController::class, 'search'])->name('api.search.customers');
        Route::get('vendors', [VendorController::class, 'search'])->name('api.search.vendors');
        Route::get('products', [ProductController::class, 'search'])->name('api.search.products');
        Route::get('categories', [CategoryController::class, 'search'])->name('api.search.categories');
        Route::get('sizes', [ProductSizeController::class, 'search'])->name('api.search.sizes');
    });

    // Categories (AJAX quick-create)
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');

    // Products & Sizes
    Route::resource('products', ProductController::class);
    Route::resource('products.sizes', ProductSizeController::class)->shallow();
    Route::post('sizes/{size}/set-price', [ProductSizeController::class, 'setPrice'])->name('sizes.set-price');
    Route::post('sizes/{size}/adjust-stock', [StockController::class, 'adjust'])->name('sizes.adjust-stock');
    Route::post('sizes/{size}/add-stock', [StockController::class, 'purchase'])->name('sizes.add-stock');
    Route::get('import-products', [ProductController::class, 'import'])->name('products.import');

    // Customers
    Route::resource('customers', CustomerController::class);
    Route::get('customers/{customer}/balance', [CustomerController::class, 'balance'])->name('customers.balance');
    Route::get('customers/{customer}/pending-invoices', [CustomerController::class, 'pendingInvoices'])->name('customers.pendingInvoices');
    Route::post('customers/{customer}/addPayments', [CustomerController::class, 'addPayments'])->name('customers.addPayments');

    // Vendors
    Route::resource('vendors', VendorController::class);
    Route::get('vendors/{vendor}/balance', [VendorController::class, 'balance'])->name('vendors.balance');
    Route::get('vendors/{vendor}/pending-invoices', [VendorController::class, 'pendingInvoices'])->name('vendors.pendingInvoices');
    Route::post('vendors/{vendor}/addPayments', [VendorController::class, 'addPayments'])->name('vendors.addPayments');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/whatsapp', [InvoiceController::class, 'whatsapp'])->name('invoices.whatsapp');

    // Purchase Invoices
    Route::resource('purchase_invoices', PurchaseInvoiceController::class);
    Route::get('purchase_invoices/{invoice}/print', [PurchaseInvoiceController::class, 'print'])->name('purchase_invoices.print');
    Route::get('purchase_invoices/{invoice}/pdf', [PurchaseInvoiceController::class, 'pdf'])->name('purchase_invoices.pdf');
    Route::get('purchase_invoices/{purchase_invoice}/whatsapp', [PurchaseInvoiceController::class, 'whatsapp'])->name('purchase_invoices.whatsapp');
    Route::post('purchase_invoices/{purchase_invoice}/updateMeta', [PurchaseInvoiceController::class, 'updateMeta'])->name('purchase_invoices.updateMeta');

    // Payments
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // Purchase Payments
    Route::post('purchase_invoices/{purchase_invoice}/payments', [PurchasePaymentController::class, 'store'])->name('purchase_payments.store');
    Route::delete('purchase_payments/{purchase_payment}', [PurchasePaymentController::class, 'destroy'])->name('purchase_payments.destroy');

    // Reports
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
    Route::get('reports/purchase_payments', [ReportController::class, 'purchasePayments'])->name('reports.purchase_payments');
    Route::get('reports/stock', [ReportController::class, 'stock'])->name('reports.stock');

    // Settings (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('settings',        [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings',        [SettingsController::class, 'update'])->name('settings.update');
        Route::get('settings/preview',[SettingsController::class, 'preview'])->name('settings.preview');
        Route::get('backup/view', [BackupController::class, 'index'])->name('backup.view');
        Route::post('backup/store', [BackupController::class, 'store'])->name('backup.store');
        Route::get('backup/download/{filename?}', [BackupController::class, 'download'])->name('backup.download');
        Route::post('backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
        // logs
        Route::get('/logs', [LogViewerController::class, 'index'])->name('admin.logs');
        Route::get('/logs/{date}/download', [LogViewerController::class, 'download'])->name('admin.logs.download');
    });
});
