<?php

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

use Modules\Product\Http\Controllers\HomeController;

Route::get('/home', [HomeController::class, 'index'])->name('api.home');

require 'productRoute.php';
require 'reviewRoute.php';
require 'subscribeRoute.php';
