<?php

use Illuminate\Http\Request;
use Modules\Setting\Http\Controllers\SettingController;

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
    Route::put('/', 'update')->name('setting.update')->middleware('auth:sanctum');
});
Route::post('/change-locale', [SettingController::class, 'changeLocale'])->name('change-locale')->middleware(\App\Http\Middleware\SetLocaleFromHeader::class);
Route::get('/global-statistics', [\Modules\Setting\Http\Controllers\StatisticController::class, 'statistics'])->name('global-statistics');
