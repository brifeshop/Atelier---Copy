<?php

use App\Http\Controllers\Finance\ArInvoiceController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Finance\ApBillController;
use App\Http\Controllers\Finance\ReportController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'verified', 'can:view finance'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/', [FinanceDashboardController::class, 'index'])->name('dashboard');

    Route::get('ar', [ArInvoiceController::class, 'index'])->name('ar.index');
    Route::get('ar/{ar}', [ArInvoiceController::class, 'show'])->name('ar.show');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/create/in', [PaymentController::class, 'createIn'])->name('payments.create-in');
    Route::post('payments/in', [PaymentController::class, 'storeIn'])->name('payments.store-in');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');

    Route::get('ap', [ApBillController::class, 'index'])->name('ap.index');
    Route::get('ap/{ap}', [ApBillController::class, 'show'])->name('ap.show');

        // Payment Out (Supplier)
    Route::get('payments/create/out', [PaymentController::class, 'createOut'])->name('payments.create-out');
    Route::post('payments/out', [PaymentController::class, 'storeOut'])->name('payments.store-out');
    Route::get('reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
    
});