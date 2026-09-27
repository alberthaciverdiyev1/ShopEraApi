<?php

Route::prefix('product')->controller(\Modules\Product\Http\Controllers\ProductSubscribeController::class)->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/subscribe', 'subscribe')->name('product.subscribe');
        Route::post('/unsubscribe', 'unsubscribe')->name('product.unsubscribe');
    });

});
