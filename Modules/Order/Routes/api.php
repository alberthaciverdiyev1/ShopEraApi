<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::prefix('order')->controller(\Modules\Order\Http\Controllers\OrderController::class)->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', 'getAll')->name('order.list');
        Route::get('/list-admin', 'getAllAdmin')->name('order.adminList');
        Route::post('/', 'orderFromBasket')->name('order.orderFromBasket');
        Route::get('/preview', 'previewOrder')->name('order.previewOrder');
        Route::post('/{product_id}', 'buyOne')->name('order.buyOne');
        Route::get('/completed', 'completedOrders')->name('order.completed');

        Route::get('/{id}', 'details')->whereNumber('id')->name('order.details');
        Route::get('/admin/{id}', 'detailsAdmin')->name('order.details-admin');
        Route::post('/admin/{id}/starex/retry', 'retryStarexShipment')->whereNumber('id')->name('order.starex-retry');

        Route::put('/{id}', 'update')->name('order.update');
        Route::delete('/{id}', 'delete')->name('order.delete');
        Route::get('/receipt/{order_id}', 'getReceipt')->name('order.getReceipt');
        Route::get('/download-receipt/{order_id}', 'downloadReceipt')->name('order.downloadReceipt');
        Route::get('/calculate-delivery-price', 'calculateDeliveryPrice')->name('order.calculateDeliveryPrice');
    });
});
