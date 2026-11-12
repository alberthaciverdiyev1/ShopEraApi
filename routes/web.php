<?php

use App\Http\Controllers\Storefront\AddressController;
use App\Http\Controllers\Storefront\AuthController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContentController;
use App\Http\Controllers\Storefront\DashboardController;
use App\Http\Controllers\Storefront\DashboardMessagesController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ListingChatController;
use App\Http\Controllers\Storefront\ListingController;
use App\Http\Controllers\Storefront\ManagedListingController;
use App\Http\Controllers\Storefront\OrderHistoryController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SettingsController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\SitemapController;
use App\Http\Controllers\Storefront\StoreController;
use App\Http\Controllers\Storefront\StorePanelController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| The API and the admin panel are served by Laravel; API routes live in
| routes/api.php. The storefront lives here too: every page is a Blade view
| served by the Storefront controllers.
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
    Route::post('/dashboard/messages', [DashboardMessagesController::class, 'send'])->name('storefront.dashboard.messages.send');
    Route::delete('/dashboard/messages/{id}', [DashboardMessagesController::class, 'destroy'])
        ->whereNumber('id')
        ->name('storefront.dashboard.messages.destroy');

    // Buyer <-> seller chat shown on the product page.
    Route::get('/mesajlar', [ListingChatController::class, 'index'])->name('storefront.chat.index');
    Route::post('/elan/{id}/chat', [ListingChatController::class, 'start'])
        ->whereNumber('id')
        ->name('storefront.chat.start');
    Route::get('/mesajlar/{id}', [ListingChatController::class, 'show'])
        ->whereNumber('id')
        ->name('storefront.chat.show');
    Route::post('/mesajlar/{id}/gonder', [ListingChatController::class, 'send'])
        ->whereNumber('id')
        ->name('storefront.chat.send');
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

    Route::get('/elanlarim', [ListingController::class, 'index'])->name('storefront.listings.index');
    Route::delete('/elanlarim/{id}', [ListingController::class, 'destroy'])
        ->whereNumber('id')
        ->name('storefront.listings.destroy');
    Route::post('/elanlarim/{id}/renew', [ListingController::class, 'renew'])
        ->whereNumber('id')
        ->name('storefront.listings.renew');
    Route::post('/elanlarim/{id}/promote', [ListingController::class, 'promote'])
        ->whereNumber('id')
        ->name('storefront.listings.promote');

    Route::post('/elan/{id}/rey', [ProductController::class, 'review'])
        ->whereNumber('id')
        ->name('storefront.product.review');

    Route::get('/magaza/panel', [StorePanelController::class, 'panel'])->name('storefront.store.panel');
    Route::put('/magaza/panel', [StorePanelController::class, 'update'])->name('storefront.store.panel.update');
    Route::get('/magaza/aciq', [StorePanelController::class, 'open'])->name('storefront.store.open');
    Route::post('/magaza/aciq', [StorePanelController::class, 'create'])->name('storefront.store.open.create');

    // Basket and checkout actions. The pages themselves are public and show a
    // sign-in prompt to guests.
    Route::match(['post', 'put'], '/cart/{id}', [CartController::class, 'update'])
        ->whereNumber('id')
        ->name('storefront.cart.update');
    Route::delete('/cart/{id}', [CartController::class, 'destroy'])
        ->whereNumber('id')
        ->name('storefront.cart.destroy');

    Route::post('/checkout/address', [CheckoutController::class, 'storeAddress'])->name('storefront.checkout.address');
    Route::post('/checkout/order', [CheckoutController::class, 'place'])->name('storefront.checkout.place');
    Route::get('/checkout/whatsapp', [CheckoutController::class, 'whatsapp'])->name('storefront.checkout.whatsapp');

    Route::get('/order/history', [OrderHistoryController::class, 'index'])->name('storefront.orders');
});

// Not linked from the navigation: kept reachable by direct URL only.
Route::get('/cart', [CartController::class, 'index'])->name('storefront.cart');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('storefront.checkout');

// Listing creation is open to guests too: their listing is managed through a
// secret link.
Route::get('/elan/ver', [ListingController::class, 'create'])->name('storefront.listings.create');
Route::post('/elan/ver', [ListingController::class, 'store'])->name('storefront.listings.store');

// Guest listings are managed through the secret link handed out at creation.
Route::get('/elan/idaresi/{token}', [ManagedListingController::class, 'show'])
    ->name('storefront.listings.manage');
Route::put('/elan/idaresi/{token}', [ManagedListingController::class, 'update'])
    ->name('storefront.listings.manage.update');
Route::delete('/elan/idaresi/{token}', [ManagedListingController::class, 'destroy'])
    ->name('storefront.listings.manage.destroy');

Route::get('/', [HomeController::class, 'index'])->name('storefront.home');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('storefront.robots');
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('storefront.sitemap');

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
// Public contact reveal for a listing: the number is fetched after a click so
// it never sits in the HTML.
Route::get('/shop/listings/{id}/contact', [ProductController::class, 'contact'])
    ->whereNumber('id')
    ->name('storefront.listing.contact');
