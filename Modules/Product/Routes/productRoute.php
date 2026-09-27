<?php

use Modules\Product\Http\Controllers\AiController;

Route::prefix('product')->controller(\Modules\Product\Http\Controllers\ProductController::class)->group(function () {

    Route::get('/statistics', 'statistics')->name('product.statistics');
    Route::get('/story-videos', 'storyVideos')->name('product.storyVideos');
    Route::match(['get','post'],'/', 'list')->name('product.list');
    Route::get('/{id}', 'details')->name('product.details')->whereNumber('id');
    Route::get('/recommend', 'recommendedProductsList')->name('product.recommendedProductsList');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/add', 'add')->name('product.add');
        Route::put('/update-prices', 'updatePrices')->name('product.updatePrices');
        Route::get('/story-videos/admin', 'storyVideosAdmin')->name('product.storyVideosAdmin');
        Route::post('/story-videos/{id}/activate', 'activateStoryVideo')->whereNumber('id')->name('product.storyVideosActivate');
        Route::post('/story-videos/{id}/deactivate', 'deactivateStoryVideo')->whereNumber('id')->name('product.storyVideosDeactivate');
        Route::get('/details/{id}', 'detailsAdmin')->whereNumber('id')->name('product.detailsAdmin');
        Route::put('/{id}', 'update')->whereNumber('id')->name('product.update');
        Route::delete('/{id}', 'delete')->whereNumber('id')->name('product.delete');
    });

});

Route::prefix('product/ai-description')->controller(AiController::class)->group(function () {
    Route::post('/', [AiController::class, 'store'])->name('product.ai-description.store');

    Route::get('/{id}/check', [AiController::class, 'check'])->name('product.ai-description.check');

    Route::get('/{id}/data', [AiController::class, 'getData'])->name('product.ai-description.data');
});
