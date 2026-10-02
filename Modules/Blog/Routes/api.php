<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;

Route::prefix('blog')->middleware('feature:blog')->controller(BlogController::class)->group(function () {
    Route::get('/', 'list')->name('blog.list');
    Route::get('/recent', 'recent')->name('blog.recent');
    Route::get('/categories', 'categories')->name('blog.categories');
    Route::get('/{slugOrId}', 'details')->name('blog.details');
});
