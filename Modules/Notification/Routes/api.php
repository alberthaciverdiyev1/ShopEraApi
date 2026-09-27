<?php

use Illuminate\Http\Request;
use Modules\Notification\Http\Controllers\SendNotificationController;

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



Route::controller(SendNotificationController::class)->middleware('auth:sanctum')->prefix('notification')->group(function () {
    Route::post('/', 'sendNotification')->name('notification.send');
});
/*
 * Cihazın tokeni girişdən əvvəl də yazılır: bildiriş qeydiyyatsız
 * istifadəçiyə də çatsın deyə. İstifadəçi daxil olanda eyni sətir onun
 * hesabına bağlanır.
 */
Route::controller(\Modules\Notification\Http\Controllers\NotificationTokenController::class)->prefix('notification')->group(function () {
    Route::post('/save-token', 'saveToken')->name('notification.save-token');
});
Route::controller(\Modules\Notification\Http\Controllers\NotificationController::class)->middleware('auth:sanctum')->prefix('notification')->group(function () {
    Route::get('/', 'getAll')->name('notification.list');
    Route::get('/admin', 'getAllAdmin')->name('notification.listAdmin');
    Route::delete('/{id}', 'delete')->name('notification.delete');

});
