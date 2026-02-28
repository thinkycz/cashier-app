<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OfflineReceiptSyncController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AresCompanyLookupController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/ares/company', AresCompanyLookupController::class)
    ->middleware('throttle:60,1')
    ->name('ares.company');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/products', [DashboardController::class, 'products'])->name('dashboard.products.index');
    Route::post('/dashboard/receipts', [DashboardController::class, 'storeReceipt'])->name('dashboard.receipts.store');
    Route::patch('/dashboard/receipts/{transaction}/customer', [DashboardController::class, 'updateReceiptCustomer'])->name('dashboard.receipts.customer');
    Route::patch('/dashboard/receipts/{transaction}/checkout', [DashboardController::class, 'checkoutReceipt'])->name('dashboard.receipts.checkout');
    Route::delete('/dashboard/receipts/{transaction}', [DashboardController::class, 'destroyReceipt'])->name('dashboard.receipts.destroy');
    Route::post('/api/offline-receipts/sync', [OfflineReceiptSyncController::class, 'store'])->name('offline.receipts.sync');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::resource('products', ProductController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('bills', BillController::class)->only(['index', 'show', 'destroy']);
    Route::get('bills/{bill}/preview', [BillController::class, 'preview'])->name('bills.preview');
    Route::post('bills/{bill}/open', [BillController::class, 'open'])->name('bills.open');
});

require __DIR__.'/auth.php';
