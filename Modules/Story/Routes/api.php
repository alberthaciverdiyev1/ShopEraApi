<?php

use Illuminate\Support\Facades\Route;
use Modules\Story\Http\Controllers\StoryController;

Route::prefix('story')->middleware('feature:stories')->controller(StoryController::class)->group(function () {
    Route::get('/', 'list')->name('story.list');
});
