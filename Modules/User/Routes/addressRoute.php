<?php

use Modules\User\Http\Controllers\UserController;

Route::controller(\Modules\User\Http\Controllers\AddressController::class)->middleware('auth:sanctum')->prefix('user/address')->group(function () {
    Route::get('/', 'getAll')->name('address.getAll');
    Route::post('/', 'add')->name('address.add');
    Route::get('/{id}', 'details')->name('address.details');
    Route::put('/{id}', 'update')->name('address.update');
    Route::delete('/{id}', 'delete')->name('address.delete');
});
