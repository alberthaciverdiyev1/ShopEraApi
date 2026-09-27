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


Route::prefix('size')->controller(\Modules\Size\Http\Controllers\SizeController::class)->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', 'list')->name('size.list');
        Route::post('/', 'add')->name('size.add');
        Route::get('/{id}', 'details')->name('size.details');
        Route::put('/{id}', 'update')->name('size.update');
        Route::delete('/{id}', 'delete')->name('size.delete');
    });
});

