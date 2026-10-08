<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DataVersionController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\PromoBlockController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
 * The rebuilt home screen. A new route on purpose: /api/product and
 * /api/category/with-products keep the exact shape the published apps parse.
 */
Route::get('/home', [HomeController::class, 'index'])->name('api.home');

// Cheap revision token for the storefront's in-memory cache.
Route::get('/data-version', [DataVersionController::class, 'index'])->name('api.data-version');

// Offer / ad blocks for the storefront.
Route::get('/promo-blocks', [PromoBlockController::class, 'index'])->name('api.promo-blocks');

// Contact info and form submission
Route::get('/contact', [ContactController::class, 'info'])->name('api.contact.info');
Route::post('/contact', [ContactController::class, 'send'])->name('api.contact.send');
