<?php

use Modules\User\Http\Controllers\UserController;

Route::get('/delete-my-account', [UserController::class, 'deleteMyAccountHtml'])->name('user.deleteMyAccountHtml');
