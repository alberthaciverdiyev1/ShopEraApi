<?php

use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\StoreAdminController;
use Modules\Store\Http\Controllers\StoreController;
use Modules\Store\Http\Controllers\StoreProductController;
use Modules\Store\Http\Controllers\StorePublicController;
use Modules\Store\Http\Controllers\StoreWithdrawalController;
use Modules\Store\Http\Controllers\StoreWalletPaymentController;

Route::prefix('store')->group(function () {
    Route::get('instructions', [StoreController::class, 'instructions'])->name('store.instructions');

    // Public storefront — no auth, mirrors what the catalogue already exposes.
    Route::get('profile/{id}', [StorePublicController::class, 'show'])->whereNumber('id')->name('store.public.show');
    Route::get('profile/{id}/products', [StorePublicController::class, 'products'])->whereNumber('id')->name('store.public.products');
    Route::get('profile/{id}/reviews', [StorePublicController::class, 'reviews'])->whereNumber('id')->name('store.public.reviews');
    Route::get('wallet/top-up/success', [StoreWalletPaymentController::class, 'success'])->name('store.wallet-topup-success');
    Route::get('wallet/top-up/error', [StoreWalletPaymentController::class, 'error'])->name('store.wallet-topup-error');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('apply', [StoreController::class, 'register'])->name('store.apply');
        Route::get('me', [StoreController::class, 'profile'])->name('store.me');
        Route::get('identity/{side}', [StoreController::class, 'identityDocument'])->name('store.identity-document');
        Route::get('wallet', [StoreController::class, 'wallet'])->name('store.wallet');
        Route::post('wallet/top-up', [StoreWalletPaymentController::class, 'start'])->name('store.wallet-topup');
        Route::get('sales', [StoreController::class, 'sales'])->name('store.sales');
        Route::get('balance', [StoreWithdrawalController::class, 'balance'])->name('store.balance');
        Route::get('withdrawals', [StoreWithdrawalController::class, 'index'])->name('store.withdrawals.index');
        Route::post('withdrawals', [StoreWithdrawalController::class, 'store'])->name('store.withdrawals.store');
        Route::post('withdrawals/{id}/cancel', [StoreWithdrawalController::class, 'cancel'])->whereNumber('id')->name('store.withdrawals.cancel');
        Route::get('orders/{orderId}/statuses', [StoreController::class, 'orderStatuses'])->whereNumber('orderId')->name('store.order-statuses');
        Route::get('fulfillments', [StoreController::class, 'fulfillments'])->name('store.fulfillments');

        Route::get('products', [StoreProductController::class, 'index'])->name('store.products.index');
        Route::post('products', [StoreProductController::class, 'store'])->name('store.products.store');
        Route::put('products/{id}', [StoreProductController::class, 'update'])->whereNumber('id')->name('store.products.update');
        Route::post('products/{id}/submit', [StoreProductController::class, 'submit'])->whereNumber('id')->name('store.products.submit');
        Route::post('products/{id}/unpublish', [StoreProductController::class, 'unpublish'])->whereNumber('id')->name('store.products.unpublish');
        Route::delete('products/{id}', [StoreProductController::class, 'destroy'])->whereNumber('id')->name('store.products.destroy');

        Route::prefix('admin')->group(function () {
            Route::get('/', [StoreAdminController::class, 'index'])->name('store.admin.index');
            Route::get('fulfillments', [StoreAdminController::class, 'fulfillments'])->name('store.admin.fulfillments');
            Route::get('products/pending', [StoreAdminController::class, 'pendingProducts'])->name('store.admin.pending-products');
            Route::get('settlements', [StoreAdminController::class, 'settlements'])->name('store.admin.settlements');
            Route::post('settlements/{id}/refund', [StoreAdminController::class, 'refundSettlement'])->whereNumber('id')->name('store.admin.settlement-refund');
            Route::get('withdrawals', [StoreAdminController::class, 'withdrawals'])->name('store.admin.withdrawals');
            Route::post('withdrawals/{id}/approve', [StoreAdminController::class, 'approveWithdrawal'])->whereNumber('id')->name('store.admin.withdrawal-approve');
            Route::post('withdrawals/{id}/pay', [StoreAdminController::class, 'payWithdrawal'])->whereNumber('id')->name('store.admin.withdrawal-pay');
            Route::post('withdrawals/{id}/reject', [StoreAdminController::class, 'rejectWithdrawal'])->whereNumber('id')->name('store.admin.withdrawal-reject');
            Route::get('audit-logs', [StoreAdminController::class, 'auditLogs'])->name('store.admin.audit-logs');
            Route::get('products/{productId}', [StoreAdminController::class, 'showProduct'])->whereNumber('productId')->name('store.admin.product-detail');
            Route::post('fulfillments/{id}/handed-over', [StoreAdminController::class, 'markHandedOver'])->whereNumber('id')->name('store.admin.fulfillments.handed-over');
            Route::get('{id}', [StoreAdminController::class, 'show'])->whereNumber('id')->name('store.admin.show');
            Route::get('{id}/products', [StoreAdminController::class, 'products'])->whereNumber('id')->name('store.admin.products');
            Route::put('{id}/status', [StoreAdminController::class, 'updateStatus'])->whereNumber('id')->name('store.admin.status');
            // Silməzdən əvvəl nəyin gedəcəyi, nəyin qalacağı göstərilir.
            Route::get('{id}/deletion-preview', [StoreAdminController::class, 'deletionPreview'])->whereNumber('id')->name('store.admin.deletion-preview');
            Route::delete('{id}', [StoreAdminController::class, 'destroy'])->whereNumber('id')->name('store.admin.destroy');
            Route::put('{id}/trust', [StoreAdminController::class, 'updateTrust'])->whereNumber('id')->name('store.admin.trust');
            Route::post('{id}/wallet', [StoreAdminController::class, 'adjustWallet'])->whereNumber('id')->name('store.admin.wallet');
            Route::get('{id}/identity/{side}', [StoreAdminController::class, 'document'])->whereNumber('id')->name('store.admin.identity-document');
            Route::put('products/{productId}/approval', [StoreAdminController::class, 'updateProductApproval'])->whereNumber('productId')->name('store.admin.product-approval');
        });
    });
});
