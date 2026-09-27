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


Route::prefix('color')->controller(\Modules\Color\Http\Controllers\ColorController::class)->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', 'list')->name('color.list');
        Route::post('/', 'add')->name('color.add');
        Route::get('/{id}', 'details')->name('color.details');
        Route::put('/{id}', 'update')->name('color.update');
        Route::delete('/{id}', 'delete')->name('color.delete');
    });
});
