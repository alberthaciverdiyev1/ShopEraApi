<?php

use Modules\User\Http\Controllers\ReferralController;
use Modules\User\Http\Controllers\ReferralSettingController;

Route::prefix('referral')->middleware(['auth:sanctum', 'feature:referrals'])->controller(ReferralController::class)->group(function () {
    Route::get('/', 'getAllUsersReferralDetails')->name('referral.details');
});
Route::get('/my-referrals', [ReferralController::class, 'getReferredUsers'])->middleware(['auth:sanctum', 'feature:referrals'])->name('referral.myReferrals');
Route::prefix('referral')->middleware(['auth:sanctum', 'feature:referrals'])->controller(ReferralSettingController::class)->group(function () {
    Route::get('/setting', 'list')->name('referral.list');
    Route::put('/setting', 'update')->name('referral.update');
});
