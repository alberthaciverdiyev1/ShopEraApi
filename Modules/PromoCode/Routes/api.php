<?php

use Illuminate\Http\Request;
use Modules\PromoCode\Http\Controllers\PromoCodeController;

Route::prefix('promo-code')->controller(PromoCodeController::class)->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', 'getAll')->name('promo-code.list');
        Route::get('/check/{code}', 'check')->name('promo-code.check');
        Route::get('/calculate-basket-discount-price/{code}', 'checkPromoCodeWithPrice')->name('promo-code.check-basket-discount-price');
       // Route::get('/calculate-one-product-discount-price/{code}', 'checkPromoCodeWithPrice')->name('promo-code.check-one-product-discount-price');
        Route::post('/', 'add')->name('promo-code.add');
        Route::get('/{id}', 'details')->name('promo-code.details');
        Route::put('/{id}', 'update')->name('promo-code.update');
        Route::delete('/{id}', 'delete')->name('promo-code.delete');
    });
});
