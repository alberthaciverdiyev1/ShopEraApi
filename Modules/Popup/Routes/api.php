<?php

use Illuminate\Http\Request;
use Modules\Popup\Http\Controllers\PopupController;

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



Route::prefix('popup')->middleware('feature:popups')->controller(PopupController::class)->group(function () {
    Route::get('/', 'list')->name('popup.list');
    Route::get('/show-one', 'showOne')->name('popup.showOne');
});

Route::middleware(['auth:sanctum', 'feature:popups'])->prefix('popup')->controller(PopupController::class)->group(function () {
    Route::delete('/{id}', 'delete')->name('popup.delete');
    Route::post('/', 'add')->name('popup.add');
    Route::put('/{id}', 'showHome')->name('popup.showHome');
});

