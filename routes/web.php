<?php

use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| The API and the admin panel are served by Laravel; API routes live in
| routes/api.php. The storefront is being rebuilt as Blade pages here, one
| page at a time; the remaining SvelteKit pages are still published to
| public/storefront by `php artisan storefront:deploy`.
|
*/

Route::get('/', [HomeController::class, 'index'])->name('storefront.home');

Route::get('/shop', [ShopController::class, 'index'])->name('storefront.shop');
Route::get('/shop/details', [ProductController::class, 'show'])->name('storefront.product.show');
Route::get('/elan/{slug}', [ProductController::class, 'showBySlug'])->name('storefront.product.slug');
// Kept outside /storefront: that path is a real directory of the old Svelte
// build, and the built-in dev server treats it as a base path.
Route::get('/shop/listings/{id}/contact', [ProductController::class, 'contact'])
    ->whereNumber('id')
    ->name('storefront.listing.contact');
