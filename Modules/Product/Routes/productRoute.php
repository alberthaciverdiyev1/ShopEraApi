<?php

use Modules\Product\Http\Controllers\AiController;
use Modules\Product\Http\Controllers\ProductController;

Route::prefix('product')->middleware('feature:products')->controller(ProductController::class)->group(function () {

    Route::get('/statistics', 'statistics')->middleware('feature:statistics')->name('product.statistics');
    Route::get('/story-videos', 'storyVideos')->middleware('feature:product_story_videos')->name('product.storyVideos');
    Route::match(['get', 'post'], '/', 'list')->name('product.list');
    Route::get('/filters', 'filters')->middleware('feature:product_filters')->name('product.filters');
    Route::get('/{id}', 'details')->name('product.details')->whereNumber('id');
    Route::get('/recommend', 'recommendedProductsList')->name('product.recommendedProductsList');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/add', 'add')->name('product.add');
        Route::put('/update-prices', 'updatePrices')->middleware('feature:bulk_price_update')->name('product.updatePrices');
        Route::get('/story-videos/admin', 'storyVideosAdmin')->middleware('feature:product_story_videos')->name('product.storyVideosAdmin');
        Route::post('/story-videos/{id}/activate', 'activateStoryVideo')->whereNumber('id')->middleware('feature:product_story_videos')->name('product.storyVideosActivate');
        Route::post('/story-videos/{id}/deactivate', 'deactivateStoryVideo')->whereNumber('id')->middleware('feature:product_story_videos')->name('product.storyVideosDeactivate');
        Route::get('/details/{id}', 'detailsAdmin')->whereNumber('id')->name('product.detailsAdmin');
        Route::put('/{id}', 'update')->whereNumber('id')->name('product.update');
        Route::delete('/{id}', 'delete')->whereNumber('id')->name('product.delete');
    });

});

Route::prefix('product/ai-description')->middleware('feature:ai_description')->controller(AiController::class)->group(function () {
    Route::post('/', [AiController::class, 'store'])->name('product.ai-description.store');

    Route::get('/{id}/check', [AiController::class, 'check'])->name('product.ai-description.check');

    Route::get('/{id}/data', [AiController::class, 'getData'])->name('product.ai-description.data');
});
