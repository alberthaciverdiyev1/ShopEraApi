<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\FeaturesController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PromoBlockController;
use App\Http\Controllers\Api\SubscriptionController;
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

// Public feature flags for the storefront (from Manager.Snaker entitlements).
Route::get('/features', [FeaturesController::class, 'index'])->name('api.features');

// Subscription status for the storefront (read-only mode, notices).
Route::get('/subscription', [SubscriptionController::class, 'show'])->name('api.subscription');

// Plan limits + current usage, mirrored from Manager.Snaker entitlements.
Route::get('/plan', [PlanController::class, 'index'])->name('api.plan');

// Offer / ad blocks managed in Manager.Snaker (Free plan only).
Route::get('/promo-blocks', [PromoBlockController::class, 'index'])->name('api.promo-blocks');

// Contact info and form submission
Route::get('/contact', [ContactController::class, 'info'])->name('api.contact.info');
Route::post('/contact', [ContactController::class, 'send'])->name('api.contact.send');
