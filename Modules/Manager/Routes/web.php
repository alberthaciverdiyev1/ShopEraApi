<?php

use Illuminate\Support\Facades\Route;
use Modules\Manager\Http\Controllers\AuthController;
use Modules\Manager\Http\Controllers\DashboardController;
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
});
