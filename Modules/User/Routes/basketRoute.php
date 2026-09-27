<?php

use Modules\User\Http\Controllers\BasketController;

Route::prefix('basket')->middleware('auth:sanctum')->controller(BasketController::class)->group(function () {
    Route::get('/', 'getAll')->name('basket.list');
    Route::post('/', 'add')->name('basket.add');
    Route::put('/{id}', 'update')->name('basket.update');
    Route::delete('/{id}', 'delete')->name('basket.delete');
});
