<?php

use App\Http\Controllers\Storefront\AddressController;
use App\Http\Controllers\Storefront\AuthController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\ContentController;
use App\Http\Controllers\Storefront\DashboardController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SettingsController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\StoreController;
use App\Http\Controllers\Storefront\WishlistController;
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

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('storefront.login');
    Route::post('/login', [AuthController::class, 'login'])->name('storefront.login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('storefront.register');
    Route::post('/register', [AuthController::class, 'register'])->name('storefront.register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('storefront.logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('storefront.dashboard');
    Route::get('/settings', [SettingsController::class, 'index'])->name('storefront.settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('storefront.settings.profile');
    Route::post('/settings/avatar', [SettingsController::class, 'updateAvatar'])->name('storefront.settings.avatar');
    Route::delete('/settings/avatar', [SettingsController::class, 'removeAvatar'])->name('storefront.settings.avatar.remove');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('storefront.wishlist');
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy'])
        ->whereNumber('id')
        ->name('storefront.wishlist.destroy');

    Route::post('/addresses', [AddressController::class, 'store'])->name('storefront.addresses.store');
    Route::put('/addresses/{id}', [AddressController::class, 'update'])
        ->whereNumber('id')
        ->name('storefront.addresses.update');
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy'])
        ->whereNumber('id')
        ->name('storefront.addresses.destroy');
});

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
