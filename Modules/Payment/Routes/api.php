<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

Route::prefix('payment')->group(function () {
    Route::get('start', [PaymentController::class, 'start'])->name('payment.start');
    Route::post('create', [PaymentController::class, 'createPayment'])->name('payment.create');
    Route::get('success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('error', [PaymentController::class, 'error'])->name('payment.error');
    Route::get('status', [PaymentController::class, 'status'])->name('payment.status');
    Route::match(['get', 'post'], 'result', [PaymentController::class, 'result'])->name('payment.result');
});

// Keep the URLs configured in the Epoint merchant panel working.
Route::get('success', [PaymentController::class, 'success'])->name('payment.epoint.success');
Route::get('error', [PaymentController::class, 'error'])->name('payment.epoint.error');
Route::post('result', [PaymentController::class, 'result'])->name('payment.epoint.result');
