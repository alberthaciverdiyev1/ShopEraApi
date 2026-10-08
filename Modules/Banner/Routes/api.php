<?php

use Illuminate\Http\Request;

Route::prefix('banner')->controller(\Modules\Banner\Http\Controllers\BannerController::class)->group(function () {

    Route::get('/', 'getAll')->name('banner.list');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'add')->name('banner.add');
        Route::delete('/{id}', 'delete')->name('banner.delete');
    });
});

