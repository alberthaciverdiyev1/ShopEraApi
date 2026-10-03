<?php

use Modules\Brand\Http\Controllers\BrandController;

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

Route::prefix('brand')->middleware('feature:brands')->controller(BrandController::class)->group(function () {

    Route::get('/', 'list')->name('brand.list');
    Route::get('/{id}', 'details')->name('brand.details');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'add')->name('brand.add');
        Route::put('/{id}', 'update')->name('brand.update');
        Route::delete('/{id}', 'delete')->name('brand.delete');
    });
});
