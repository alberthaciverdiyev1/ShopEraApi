<?php

use Modules\HelpAndPolicy\Http\Controllers\FaqController;
use Modules\HelpAndPolicy\Http\Controllers\LegalTermController;

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

Route::prefix('faq')->controller(FaqController::class)->group(function () {
    Route::get('/', 'getAll')->name('faq.list');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/admin', 'getAllAdmin')->name('faq.listAdmin');
        Route::post('/', 'add')->name('faq.add');
        Route::put('/{id}', 'update')->name('faq.update');
        Route::delete('/{id}', 'delete')->name('faq.delete');
    });

});

Route::prefix('legal-terms')->controller(LegalTermController::class)->group(function () {
    Route::get('/', 'getAll')->name('legal_terms.list');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/admin', 'getAllAdmin')->name('legal_terms.listAdmin');
        Route::put('/{type}', 'update')->name('legal_terms.update');
    });

});
