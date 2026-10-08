<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\ListingController;

/*
| Marketplace listings.
|
| Guest listings need no account and are managed through a secret link
| (PUT/DELETE /listings/manage/{token}). Signed-in sellers (users and vendors)
| manage their listings under the authenticated group.
*/
Route::prefix('listings')->controller(ListingController::class)->group(function () {
    Route::get('/', 'index')->name('listing.index');
    Route::post('/guest', 'storeGuest')->name('listing.guest');

    // Token routes must come before the numeric {id} route.
    Route::get('/manage/{token}', 'manageShow')->name('listing.manage.show');
    Route::put('/manage/{token}', 'manageUpdate')->name('listing.manage.update');
    Route::delete('/manage/{token}', 'manageDestroy')->name('listing.manage.destroy');

    Route::get('/{id}', 'show')->whereNumber('id')->name('listing.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'store')->name('listing.store');
        Route::get('/mine', 'myListings')->name('listing.mine');
        Route::put('/{id}', 'update')->whereNumber('id')->name('listing.update');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('listing.destroy');
    });
});
