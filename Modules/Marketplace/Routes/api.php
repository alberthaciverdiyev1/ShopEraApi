<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\ListingChatController;
use Modules\Marketplace\Http\Controllers\ListingController;
use Modules\Marketplace\Http\Controllers\VendorController;
use Modules\Marketplace\Http\Controllers\VendorTeamController;

/*
| Marketplace listing writing/management.
|
| Products are the listings: the public read side already exists on the
| /product endpoints, so nothing is duplicated here. These routes only cover
| posting and managing a listing, and the buyer<->seller chat per listing.
*/
Route::prefix('listings')->controller(ListingController::class)->group(function () {
    Route::get('/fields', 'fields')->name('listing.fields');
    Route::get('/promotion-packages', 'packages')->name('listing.packages');
    Route::post('/guest', 'storeGuest')->middleware('throttle:guest-listings')->name('listing.guest');

    // Secret-link management for guest listings.
    Route::get('/manage/{token}', 'manageShow')->name('listing.manage.show');
    Route::put('/manage/{token}', 'manageUpdate')->name('listing.manage.update');
    Route::delete('/manage/{token}', 'manageDestroy')->name('listing.manage.destroy');

    // Public: reveal the seller's contact details.
    Route::get('/{id}/contact', 'contact')->whereNumber('id')->name('listing.contact');

    // Public: report a listing.
    Route::post('/{id}/report', 'report')->whereNumber('id')->middleware('throttle:reports')->name('listing.report');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'store')->middleware('throttle:listings')->name('listing.store');
        Route::post('/{id}/promote', 'promote')->whereNumber('id')->middleware('throttle:listings')->name('listing.promote');
        Route::post('/{id}/renew', 'renew')->whereNumber('id')->middleware('throttle:listings')->name('listing.renew');
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
    Route::post('/chats/{id}/messages', 'send')->whereNumber('id')->middleware('throttle:chat')->name('listing.chats.send');
    Route::post('/{id}/chat', 'start')->whereNumber('id')->name('listing.chat.start');
});

// Stores (vendors): public profile + signed-in management.
Route::prefix('vendors')->controller(VendorController::class)->group(function () {
    Route::get('/', 'index')->name('vendor.list');
    Route::get('/{slug}', 'show')->name('vendor.show');
});

Route::prefix('vendor')->controller(VendorController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'mine')->name('vendor.mine');
    Route::post('/', 'create')->name('vendor.create');
    Route::put('/', 'update')->name('vendor.update');
});

// Store team: the owner manages members by invitation link.
Route::prefix('vendor/team')->controller(VendorTeamController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->name('vendor.team');
    Route::get('/definitions', 'definitions')->name('vendor.team.definitions');
    Route::post('/invitations', 'invite')->name('vendor.team.invite');
    Route::delete('/invitations/{token}', 'destroyInvitation')->name('vendor.team.invitation.destroy');
    Route::put('/members/{user}', 'updateMember')->whereNumber('user')->name('vendor.team.member.update');
    Route::delete('/members/{user}', 'destroyMember')->whereNumber('user')->name('vendor.team.member.destroy');
    Route::post('/invitations/{token}/accept', 'accept')->name('vendor.team.accept');
});

// Opening an invitation link: the token itself is the credential.
Route::get('/vendor/invitations/{token}', [VendorTeamController::class, 'invitation'])
    ->name('vendor.invitation');
