<?php

use Modules\Delivery\Http\Controllers\CityController;
use Modules\Delivery\Http\Controllers\DeliveryController;
use Modules\Delivery\Http\Controllers\DeliveryInfoController;
use Modules\Delivery\Http\Controllers\PickupPointController;

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

Route::prefix('delivery')->controller(DeliveryController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'list')->name('delivery.list');
    Route::post('/', 'add')->name('delivery.add');
    Route::get('/details', 'details')->name('delivery.details');
    Route::get('/details-mobile', 'detailsForMobile')->name('delivery.detailsForMobile');
    Route::put('/{id}', 'update')->name('delivery.update');
    Route::delete('/{id}', 'delete')->name('delivery.delete');
});
Route::get('city', [DeliveryController::class, 'cities'])->name('city.list');

Route::prefix('pickup-point')->controller(PickupPointController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'getAll')->name('pickup-point.list')->withoutMiddleware('auth:sanctum');
    Route::post('/', 'add')->name('pickup-point.add');
    Route::get('/details', 'details')->name('pickup-point.details');
    Route::get('/{id}', 'detailsAdmin')->name('pickup-point.detailsAdmin');

    Route::put('/{id}', 'update')->name('pickup-point.update');
    Route::delete('/{id}', 'delete')->name('pickup-point.delete');
});

Route::prefix('delivery-city')->controller(CityController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'getAll')->name('delivery-city.list')->withoutMiddleware('auth:sanctum');
    Route::post('/', 'add')->name('delivery-city.add');
    Route::get('/details/{id}', 'details')->name('delivery-city.details');
    Route::put('/{id}', 'update')->name('delivery-city.update');
    Route::delete('/{id}', 'delete')->name('delivery-city.delete');
});

Route::prefix('delivery-info')
    ->controller(DeliveryInfoController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'getAll')->name('delivery-info.list')->withoutMiddleware('auth:sanctum');
        Route::put('/{id}', 'update')->name('delivery-info.update');
        Route::get('/{id}', 'getByType')->name('delivery-info.getById');
    });
