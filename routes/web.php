<?php

use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\ContentController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\StoreController;
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
Route::get('/categories', [CategoryController::class, 'index'])->name('storefront.categories');
Route::get('/blog', [BlogController::class, 'index'])->name('storefront.blog');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('storefront.blog.show');
Route::get('/magaza/{slug}', [StoreController::class, 'show'])->name('storefront.store');
Route::get('/about', [ContentController::class, 'about'])->name('storefront.about');
Route::get('/faq', [ContentController::class, 'faq'])->name('storefront.faq');
Route::get('/privacy', [ContentController::class, 'privacy'])->name('storefront.privacy');
Route::get('/terms', [ContentController::class, 'terms'])->name('storefront.terms');
Route::get('/contact', [ContentController::class, 'contact'])->name('storefront.contact');
Route::post('/contact', [ContentController::class, 'sendContact'])->name('storefront.contact.send');
Route::get('/shop/details', [ProductController::class, 'show'])->name('storefront.product.show');
Route::get('/elan/{slug}', [ProductController::class, 'showBySlug'])->name('storefront.product.slug');
// Kept outside /storefront: that path is a real directory of the old Svelte
// build, and the built-in dev server treats it as a base path.
Route::get('/shop/listings/{id}/contact', [ProductController::class, 'contact'])
    ->whereNumber('id')
    ->name('storefront.listing.contact');
