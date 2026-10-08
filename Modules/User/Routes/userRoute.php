<?php

use Modules\User\Http\Controllers\UserController;

Route::controller(UserController::class)->middleware(['auth:sanctum'])->prefix('user')->group(function () {
    Route::put('/change-email', 'changeEmail')->name('auth.changeEmail');
    Route::put('/change-name', 'changeName')->name('auth.changeName');
    Route::put('/change-surname', 'changeSurname')->name('auth.changeSurname');
    Route::put('/change-phone', 'changePhone')->name('auth.changePhone');
    Route::post('/change-avatar', 'changeAvatar')->name('auth.changeAvatar');
    Route::delete('/remove-avatar', 'removeAvatar')->name('auth.removeAvatar');
    Route::get('/list', 'getAll')->name('user.list');
    Route::post('/block', 'blockUser')->name('user.block');
    Route::put('/wholesaler-status', 'changeWholesalerStatus')->name('user.wholesaler-status');
    Route::get('/details/{id?}', 'details')->name('user.details');
    Route::delete('/delete-admin/{id}', 'delete')->name('user.delete');
    Route::delete('/delete', 'deleteMyAccount')->name('user.deleteMyAccount');
});
