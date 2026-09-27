<?php

use Modules\User\Http\Controllers\FavoritesController;

Route::prefix('favorite')->middleware('auth:sanctum')->controller(FavoritesController::class)->group(function () {
    Route::get('/', 'list')->name('favorite.list');
    Route::post('/{id}', 'add')->name('favorite.add');
    Route::delete('/{id}', 'delete')->name('favorite.delete');
});
