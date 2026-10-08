<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\ListingChatController;
use Modules\Marketplace\Http\Controllers\ListingController;

/*
| Marketplace listing writing/management.
|
| Products are the listings: the public read side already exists on the
| /product endpoints, so nothing is duplicated here. These routes only cover
| posting and managing a listing, and the buyer<->seller chat per listing.
*/
Route::prefix('listings')->controller(ListingController::class)->group(function () {
    Route::get('/fields', 'fields')->name('listing.fields');
    Route::post('/guest', 'storeGuest')->name('listing.guest');

    // Secret-link management for guest listings.
    Route::get('/manage/{token}', 'manageShow')->name('listing.manage.show');
    Route::put('/manage/{token}', 'manageUpdate')->name('listing.manage.update');
    Route::delete('/manage/{token}', 'manageDestroy')->name('listing.manage.destroy');

    // Public: reveal the seller's contact details.
    Route::get('/{id}/contact', 'contact')->whereNumber('id')->name('listing.contact');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'store')->name('listing.store');
        Route::get('/mine', 'myListings')->name('listing.mine');
        Route::put('/{id}', 'update')->whereNumber('id')->name('listing.update');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('listing.destroy');
    });
});

// Buyer <-> seller chat (signed-in only).
Route::prefix('listings')->controller(ListingChatController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/chats', 'index')->name('listing.chats');
    Route::get('/chats/unread', 'unread')->name('listing.chats.unread');
    Route::get('/chats/{id}', 'show')->whereNumber('id')->name('listing.chats.show');
    Route::post('/chats/{id}/messages', 'send')->whereNumber('id')->name('listing.chats.send');
    Route::post('/{id}/chat', 'start')->whereNumber('id')->name('listing.chat.start');
});
