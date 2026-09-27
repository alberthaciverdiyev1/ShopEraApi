<?php

use Modules\Filter\Http\Controllers\FilterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public reads.
Route::prefix('filters')->controller(FilterController::class)->group(function () {
    Route::get('/', 'list')->name('filter.list');
});

Route::prefix('category-filters')->controller(FilterController::class)->group(function () {
    Route::get('/', 'categoryFilters')->name('filter.category');
});

Route::prefix('filter')->controller(FilterController::class)->group(function () {
    Route::get('/{id}', 'details')->whereNumber('id')->name('filter.details');
    Route::post('/', 'add')->name('filter.add');
    Route::put('/{id}', 'update')->whereNumber('id')->name('filter.update');
    Route::delete('/{id}', 'delete')->whereNumber('id')->name('filter.delete');
    Route::put('/{id}/categories', 'setCategories')->whereNumber('id')->name('filter.categories');
});

Route::prefix('product-filters')->controller(FilterController::class)->group(function () {
    Route::get('/', 'productValues')->name('filter.product');
    Route::put('/', 'setProductValues')->name('filter.product.update');
});
