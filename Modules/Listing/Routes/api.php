<?php

use Illuminate\Support\Facades\Route;
use Modules\Listing\Http\Controllers\ListingController;
use Modules\Listing\Http\Controllers\ListingModerationController;
use Modules\Listing\Http\Controllers\ListingFieldAdminController;
use Modules\Listing\Http\Controllers\ListingFieldController;
use Modules\Listing\Http\Controllers\ListingGeocodeController;
use Modules\Listing\Http\Controllers\ListingMediaController;
use Modules\Listing\Http\Controllers\ListingSectionAdminController;
use Modules\Listing\Http\Controllers\ListingSectionController;

/*
 * Every route here is new. Nothing the shipped apps already call is touched,
 * so a build that predates the classifieds never reaches this file.
 */
Route::prefix('listings')->group(function () {
    // Browsing is open: an account is only needed to post an ad.
    Route::get('config', [ListingSectionController::class, 'config'])->name('listings.config');
    Route::get('sections', [ListingSectionController::class, 'index'])->name('listings.sections.index');
    Route::get('sections/{key}', [ListingSectionController::class, 'show'])->name('listings.sections.show');
    Route::get('fields/{field}/options', [ListingFieldController::class, 'options'])->whereNumber('field')->name('listings.fields.options');
    Route::get('cities', [ListingSectionController::class, 'cities'])->name('listings.cities');

    // Xəritə sahəsinin axtarışı. Tətbiq geocoder-ə birbaşa getmir: OSM
    // paylanan tətbiqin öz serveri üzərindən keçməsini istəyir, həm də belə
    // olanda provayderi dəyişmək üçün yeni build lazım gəlmir.
    Route::get('geocode', [ListingGeocodeController::class, 'search'])
        ->middleware('throttle:60,1')->name('listings.geocode');
    Route::get('geocode/reverse', [ListingGeocodeController::class, 'reverse'])
        ->middleware('throttle:60,1')->name('listings.geocode.reverse');

    Route::middleware('auth:sanctum')->group(function () {
        // Before the {id} routes, so "mine" is never read as an id.
        Route::get('mine', [ListingController::class, 'mine'])->name('listings.mine');
        Route::post('/', [ListingController::class, 'store'])->name('listings.store');
        Route::post('{id}', [ListingController::class, 'update'])->whereNumber('id')->name('listings.update');
        Route::delete('{id}', [ListingController::class, 'destroy'])->whereNumber('id')->name('listings.destroy');
        Route::post('{id}/renew', [ListingController::class, 'renew'])->whereNumber('id')->name('listings.renew');

        // Photos come in batches, because a request is capped at 30 seconds.
        Route::delete('media/{media}', [ListingMediaController::class, 'destroy'])->whereNumber('media')->name('listings.media.destroy');
        Route::post('{id}/media', [ListingMediaController::class, 'store'])->whereNumber('id')->name('listings.media.store');
        Route::post('{id}/media/order', [ListingMediaController::class, 'sort'])->whereNumber('id')->name('listings.media.sort');
        Route::post('{id}/archive', [ListingController::class, 'archive'])->whereNumber('id')->name('listings.archive');
    });

    Route::get('/', [ListingController::class, 'index'])->name('listings.index');
    Route::get('{id}', [ListingController::class, 'show'])->whereNumber('id')->name('listings.show');
    // Loaded after the page, so an empty answer just hides the rail.
    Route::get('{id}/similar', [ListingController::class, 'similar'])->whereNumber('id')->name('listings.similar');
});

Route::prefix('admin/listings')->middleware('auth:sanctum')->group(function () {
    Route::get('sections', [ListingSectionAdminController::class, 'index'])->name('listings.admin.sections.index');
    Route::post('sections', [ListingSectionAdminController::class, 'store'])->name('listings.admin.sections.store');
    // POST rather than PUT: the panel sends images as multipart.
    Route::post('sections/{id}', [ListingSectionAdminController::class, 'update'])->whereNumber('id')->name('listings.admin.sections.update');
    Route::delete('sections/{id}', [ListingSectionAdminController::class, 'destroy'])->whereNumber('id')->name('listings.admin.sections.destroy');

    Route::post('sections/{id}/fields', [ListingFieldAdminController::class, 'store'])->whereNumber('id')->name('listings.admin.fields.store');
    Route::post('fields/{id}', [ListingFieldAdminController::class, 'update'])->whereNumber('id')->name('listings.admin.fields.update');
    Route::delete('fields/{id}', [ListingFieldAdminController::class, 'destroy'])->whereNumber('id')->name('listings.admin.fields.destroy');
    Route::post('fields/{id}/options', [ListingFieldAdminController::class, 'saveOptions'])->whereNumber('id')->name('listings.admin.options.save');
    Route::delete('options/{id}', [ListingFieldAdminController::class, 'destroyOption'])->whereNumber('id')->name('listings.admin.options.destroy');

    // Moderation. The queue is what the screen opens on; every other status is
    // reachable with ?status=.
    Route::get('ads', [ListingModerationController::class, 'index'])->name('listings.admin.ads.index');
    Route::get('ads/{id}', [ListingModerationController::class, 'show'])->whereNumber('id')->name('listings.admin.ads.show');
    Route::post('ads/{id}/approve', [ListingModerationController::class, 'approve'])->whereNumber('id')->name('listings.admin.ads.approve');
    Route::post('ads/{id}/reject', [ListingModerationController::class, 'reject'])->whereNumber('id')->name('listings.admin.ads.reject');
    Route::post('ads/{id}/feature', [ListingModerationController::class, 'feature'])->whereNumber('id')->name('listings.admin.ads.feature');
    Route::delete('ads/{id}', [ListingModerationController::class, 'destroy'])->whereNumber('id')->name('listings.admin.ads.destroy');
});
