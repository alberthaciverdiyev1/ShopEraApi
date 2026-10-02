<?php

use Illuminate\Support\Facades\Route;
use Modules\Manager\Http\Controllers\AuthController;
use Modules\Manager\Http\Controllers\DashboardController;
use Modules\Manager\Http\Controllers\FeatureController;
use Modules\Manager\Http\Controllers\OwnerController;
use Modules\Manager\Http\Controllers\PlanController;
use Modules\Manager\Http\Controllers\PromoBlockController;
use Modules\Manager\Http\Controllers\ThemeController;
use Modules\Manager\Http\Middleware\EnsureOwner;

/*
|--------------------------------------------------------------------------
| Manager (SaaS operator) panel
|--------------------------------------------------------------------------
| Served on the configured control host (tenant.control_hosts). Access is
| limited to the single `owner` role account.
*/

Route::middleware('guest:owner')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'attempt'])->name('login.attempt');
});

Route::middleware(['auth:owner', EnsureOwner::class])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Site owners (customers) managed by domain
    Route::resource('owners', OwnerController::class)->except(['show']);
    Route::put('owners/{owner}/features', [OwnerController::class, 'updateFeatures'])->name('owners.features');

    // Plans & the feature matrix
    Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
    Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');

    // Feature catalogue
    Route::resource('features', FeatureController::class)->except(['show']);
    Route::post('features/{feature}/duplicate', [FeatureController::class, 'duplicate'])->name('features.duplicate');

    // Themes (presets)
    Route::resource('themes', ThemeController::class)->except(['show']);
    Route::post('themes/{theme}/default', [ThemeController::class, 'makeDefault'])->name('themes.default');

    // Promo / offer blocks
    Route::resource('promo-blocks', PromoBlockController::class)->except(['show'])
        ->parameters(['promo-blocks' => 'promo_block']);
});
