<?php

use Modules\User\Http\Controllers\AuthController;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register')->name('auth.register');
    Route::post('login', 'login')->name('auth.login');
    Route::post('send-otp', 'sendOtp')->middleware('feature:sms')->name('auth.sendOtp');
    Route::post('check-otp', 'checkOtp')->middleware('feature:sms')->name('auth.checkOtp');
    Route::post('reset-password', 'resetPassword')->name('auth.resetPassword');
    // E-poçt ilə bərpa: kod göndər, sonra kodla yeni şifrə.
    Route::post('password/email-code', 'sendPasswordResetEmail')->name('auth.password.email-code');
    Route::post('password/email-reset', 'resetPasswordByEmail')->name('auth.password.email-reset');
    Route::post('password-reset-requests', 'createPasswordResetRequest')->name('auth.password-reset-requests.create');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'logout')->name('auth.logout');
        Route::post('change-password', 'changePassword')->name('auth.changePassword');
        Route::post('admin-change-password', 'adminChangePassword')->name('auth.adminChangePassword');
        Route::get('password-reset-requests', 'passwordResetRequests')->name('auth.password-reset-requests.index');
        Route::put('password-reset-requests/{id}/resolve', 'resolvePasswordResetRequest')->whereNumber('id')->name('auth.password-reset-requests.resolve');
        Route::put('password-reset-requests/{id}/dismiss', 'dismissPasswordResetRequest')->whereNumber('id')->name('auth.password-reset-requests.dismiss');
    });
});
