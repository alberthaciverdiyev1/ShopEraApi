<?php

use App\Http\Middleware\SetLocaleFromHeader;
use Modules\Setting\Http\Controllers\ContactController;
use Modules\Setting\Http\Controllers\DataVersionController;
use Modules\Setting\Http\Controllers\PromoBlockController;
use Modules\Setting\Http\Controllers\SettingController;
use Modules\Setting\Http\Controllers\StatisticController;
use Modules\Setting\Http\Controllers\ThemeColorController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('setting')->controller(SettingController::class)->group(function () {
    Route::get('/', 'list')->name('setting.list');
    Route::put('/', 'update')->name('setting.update')->middleware(['auth:sanctum']);
});
Route::post('/change-locale', [SettingController::class, 'changeLocale'])->name('change-locale')->middleware([SetLocaleFromHeader::class]);
Route::get('/global-statistics', [StatisticController::class, 'statistics'])->name('global-statistics');

/*
 * Storefront colour palette. GET is public so the site can theme itself;
 * PUT requires the `update theme` permission.
 */
Route::prefix('theme')->controller(ThemeColorController::class)->group(function () {
    Route::get('/', 'show')->name('theme.show');
    Route::put('/', 'update')->name('theme.update');
});

// Contact info and form submission.
Route::get('/contact', [ContactController::class, 'info'])->name('api.contact.info');
Route::post('/contact', [ContactController::class, 'send'])->name('api.contact.send');

// Cheap revision token for clients' in-memory caches.
Route::get('/data-version', [DataVersionController::class, 'index'])->name('api.data-version');

// Offer / ad blocks for the storefront.
Route::get('/promo-blocks', [PromoBlockController::class, 'index'])->name('api.promo-blocks');
