<?php

use Illuminate\Http\Request;


Route::prefix('review')->middleware('auth:sanctum')->controller(\Modules\Product\Http\Controllers\ReviewController::class)->group(function () {
    Route::get('/list-admin', 'listAdmin')->name('review.listAdmin');
    Route::post('/', 'add')->name('review.add');
    Route::put('/change-status', 'changeStatus')->name('review.changeStatus');
    Route::delete('/{id}', 'delete')->name('review.delete');
    Route::delete('/admin/{id}', 'deleteByAdmin')->name('review.deleteByAdmin');
});
Route::get('review/{product_id}', [\Modules\Product\Http\Controllers\ReviewController::class, 'list'])->name('review.list');

