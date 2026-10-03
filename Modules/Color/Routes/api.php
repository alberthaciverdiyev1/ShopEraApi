<?php

use Modules\Color\Http\Controllers\ColorController;

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

Route::prefix('color')->middleware('feature:colors')->controller(ColorController::class)->group(function () {

    // Public: the storefront builds its filters from these lists.
    Route::get('/', 'list')->name('color.list');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'add')->name('color.add');
        Route::get('/{id}', 'details')->name('color.details');
        Route::put('/{id}', 'update')->name('color.update');
        Route::delete('/{id}', 'delete')->name('color.delete');
    });
});
