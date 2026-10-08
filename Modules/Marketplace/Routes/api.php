<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\ListingController;

/*
| Marketplace listing writing/management.
|
| Products are the listings: the public read side already exists on the
| /product endpoints, so nothing is duplicated here. These routes only cover
| posting and managing a listing.
|
| Guests post without an account and manage their listing through a secret
| link (PUT/DELETE /listings/manage/{token}); signed-in sellers use the
| authenticated group.
*/
Route::prefix('listings')->controller(ListingController::class)->group(function () {
    Route::get('/fields', 'fields')->name('listing.fields');
    Route::post('/guest', 'storeGuest')->name('listing.guest');

    // Secret-link management for guest listings.
    Route::get('/manage/{token}', 'manageShow')->name('listing.manage.show');
    Route::put('/manage/{token}', 'manageUpdate')->name('listing.manage.update');
    Route::delete('/manage/{token}', 'manageDestroy')->name('listing.manage.destroy');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'store')->name('listing.store');
        Route::get('/mine', 'myListings')->name('listing.mine');
        Route::put('/{id}', 'update')->whereNumber('id')->name('listing.update');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('listing.destroy');
    });
});
