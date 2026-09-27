<?php

use Illuminate\Support\Facades\Route;
use Modules\Live\Http\Controllers\LiveAdminController;
use Modules\Live\Http\Controllers\LiveController;

/*
 * Every route here is new. Nothing the shipped app already calls is touched,
 * so a build that predates live shopping simply never reaches this file.
 */
Route::prefix('live')->group(function () {
    // Watching is open to everyone. An account is only required at the point
    // of writing, liking or buying.
    Route::get('current', [LiveController::class, 'current'])->name('live.current');
    Route::get('/', [LiveController::class, 'index'])->name('live.index');
    Route::get('{id}', [LiveController::class, 'show'])->whereNumber('id')->name('live.show');
    Route::get('{id}/messages', [LiveController::class, 'messages'])->whereNumber('id')->name('live.messages');
    Route::post('{id}/heartbeat', [LiveController::class, 'heartbeat'])->whereNumber('id')->name('live.heartbeat');
    Route::post('{id}/events', [LiveController::class, 'track'])->whereNumber('id')->name('live.events');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('{id}/messages', [LiveController::class, 'sendMessage'])->whereNumber('id')->name('live.messages.store');
        Route::post('{id}/like', [LiveController::class, 'like'])->whereNumber('id')->name('live.like');
    });
});

Route::prefix('admin/live')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [LiveAdminController::class, 'index'])->name('live.admin.index');
    Route::post('/', [LiveAdminController::class, 'store'])->name('live.admin.store');
    Route::get('{id}', [LiveAdminController::class, 'show'])->whereNumber('id')->name('live.admin.show');
    Route::post('{id}', [LiveAdminController::class, 'update'])->whereNumber('id')->name('live.admin.update');
    Route::delete('{id}', [LiveAdminController::class, 'destroy'])->whereNumber('id')->name('live.admin.destroy');

    Route::post('{id}/products', [LiveAdminController::class, 'addProduct'])->whereNumber('id')->name('live.admin.products.add');
    Route::delete('{id}/products/{productId}', [LiveAdminController::class, 'removeProduct'])
        ->whereNumber('id')->whereNumber('productId')->name('live.admin.products.remove');
    Route::post('{id}/active-product', [LiveAdminController::class, 'setActiveProduct'])->whereNumber('id')->name('live.admin.active-product');
    Route::post('{id}/start', [LiveAdminController::class, 'start'])->whereNumber('id')->name('live.admin.start');
    Route::post('{id}/end', [LiveAdminController::class, 'end'])->whereNumber('id')->name('live.admin.end');

    Route::get('{id}/messages', [LiveAdminController::class, 'messages'])->whereNumber('id')->name('live.admin.messages');
    Route::delete('{id}/messages/{messageId}', [LiveAdminController::class, 'deleteMessage'])
        ->whereNumber('id')->whereNumber('messageId')->name('live.admin.messages.delete');
    Route::post('{id}/mute', [LiveAdminController::class, 'mute'])->whereNumber('id')->name('live.admin.mute');
    Route::post('{id}/block', [LiveAdminController::class, 'block'])->whereNumber('id')->name('live.admin.block');
    Route::post('{id}/lift', [LiveAdminController::class, 'lift'])->whereNumber('id')->name('live.admin.lift');
    Route::get('{id}/restrictions', [LiveAdminController::class, 'restrictions'])->whereNumber('id')->name('live.admin.restrictions');

    Route::get('{id}/statistics', [LiveAdminController::class, 'statistics'])->whereNumber('id')->name('live.admin.statistics');
});
