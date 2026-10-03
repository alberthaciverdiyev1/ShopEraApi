<?php

use Modules\Product\Http\Controllers\ReviewController;

Route::prefix('review')->middleware(['auth:sanctum', 'feature:reviews'])->controller(ReviewController::class)->group(function () {
    Route::get('/list-admin', 'listAdmin')->name('review.listAdmin');
    Route::post('/', 'add')->name('review.add');
    Route::put('/change-status', 'changeStatus')->name('review.changeStatus');
    Route::delete('/{id}', 'delete')->name('review.delete');
    Route::delete('/admin/{id}', 'deleteByAdmin')->name('review.deleteByAdmin');
});
Route::get('review/featured', [ReviewController::class, 'featured'])->middleware('feature:reviews')->name('review.featured');
Route::get('review/{product_id}', [ReviewController::class, 'list'])->middleware('feature:reviews')->name('review.list');
