<?php

use Modules\Order\Http\Controllers\OrderController;

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

Route::prefix('order')->middleware('feature:orders')->controller(OrderController::class)->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', 'getAll')->name('order.list');
        Route::get('/list-admin', 'getAllAdmin')->name('order.adminList');
        Route::post('/', 'orderFromBasket')->name('order.orderFromBasket');
        Route::get('/preview', 'previewOrder')->name('order.previewOrder');
        Route::post('/{product_id}', 'buyOne')->name('order.buyOne');
        Route::get('/whatsapp-link', 'whatsappLink')->middleware('feature:whatsapp_orders')->name('order.whatsappLink');
        Route::get('/completed', 'completedOrders')->name('order.completed');

        Route::get('/{id}', 'details')->whereNumber('id')->name('order.details');
        Route::get('/admin/{id}', 'detailsAdmin')->name('order.details-admin');

        Route::put('/{id}', 'update')->name('order.update');
        Route::delete('/{id}', 'delete')->name('order.delete');
        Route::get('/receipt/{order_id}', 'getReceipt')->middleware('feature:order_receipt')->name('order.getReceipt');
        Route::get('/download-receipt/{order_id}', 'downloadReceipt')->middleware('feature:order_receipt')->name('order.downloadReceipt');
        Route::get('/calculate-delivery-price', 'calculateDeliveryPrice')->name('order.calculateDeliveryPrice');
    });
});
