<?php

use Modules\Product\Http\Controllers\ProductSubscribeController;

Route::prefix('product')->controller(ProductSubscribeController::class)->group(function () {
    Route::middleware(['auth:sanctum', 'feature:stock_subscriptions'])->group(function () {
        Route::post('/subscribe', 'subscribe')->name('product.subscribe');
        Route::post('/unsubscribe', 'unsubscribe')->name('product.unsubscribe');
    });

});
