<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermissions\Http\Controllers\RoleController;
use Modules\RoleAndPermissions\Http\Controllers\PermissionController;

/*
|--------------------------------------------------------------------------
| Role Routes
|--------------------------------------------------------------------------
*/
Route::prefix('role')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [RoleController::class, 'getAll']);
    Route::post('/', [RoleController::class, 'add']);
    Route::get('/{id}', [RoleController::class, 'details']);
    Route::put('/{role}', [RoleController::class, 'update']);
    Route::delete('/{role}', [RoleController::class, 'delete']);

    Route::post('/{role}/give-permission', [RoleController::class, 'givePermission']);
    Route::post('/{role}/revoke-permission', [RoleController::class, 'revokePermission']);

    Route::post('/assign-role/{userId}', [RoleController::class, 'assignRoleToUser']);
    Route::post('/revoke-role/{userId}', [RoleController::class, 'revokeRoleFromUser']);
});

/*
|--------------------------------------------------------------------------
| Permission Routes
|--------------------------------------------------------------------------
*/
Route::prefix('permission')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [PermissionController::class, 'getAll']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::get('/{id}', [PermissionController::class, 'show']);
    Route::put('/{permission}', [PermissionController::class, 'update']);
    Route::delete('/{permission}', [PermissionController::class, 'delete']);
});


