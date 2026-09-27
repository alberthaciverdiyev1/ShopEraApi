<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/integrations/starex/webhook', \App\Http\Controllers\StarexWebhookController::class)
    ->name('integrations.starex.webhook');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
 * The rebuilt home screen. A new route on purpose: /api/product and
 * /api/category/with-products keep the exact shape the published apps parse.
 */
Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'index'])->name('api.home');
