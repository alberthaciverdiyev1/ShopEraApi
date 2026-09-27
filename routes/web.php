<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\ListingWebController;
use App\Http\Controllers\Web\StorefrontController;
use App\Http\Controllers\AppLinkController;

Route::get('/', [StorefrontController::class, 'home'])->name('storefront.home');
Route::get('/mehsullar', [StorefrontController::class, 'products'])->name('storefront.products');
Route::get('/mehsullar/{product}', [StorefrontController::class, 'product'])->name('storefront.product')->whereNumber('product');
Route::get('/kateqoriyalar', [StorefrontController::class, 'categories'])->name('storefront.categories');

/*
 * Elanlar. `/elan/{id}` tətbiqin association fayllarında elan edilir, ona görə
 * telefonda link tətbiqdə açılır; tətbiq yoxdursa eyni elan burada görünür.
 * Bölmə siyahısı ayrıca prefiksdədir ki, universal link onu tətbiqə
 * yönəltməsin.
 */
Route::get('/elanlar', [ListingWebController::class, 'index'])->name('storefront.listings');
Route::get('/elanlar/{section}', [ListingWebController::class, 'section'])
    ->where('section', '[a-z0-9\-]+')->name('storefront.listings.section');
Route::get('/elan/{id}', [ListingWebController::class, 'show'])
    ->whereNumber('id')->name('storefront.listing');

// Shared live stream links. The app claims this path through the association
// files, so on a phone the OS opens the stream in the app; anyone else is sent
// to the same broadcast on YouTube instead of a dead page.
Route::get('/canli/{id}', [\App\Http\Controllers\Web\LiveShareController::class, 'show'])
    ->whereNumber('id')->name('storefront.live');

// The embed host for the app's player. Deliberately not under /canli — that
// prefix is claimed by the app through the association files, and a universal
// link would have the phone try to reopen the app instead of loading the page.
Route::get('/live-player/{video}', [\App\Http\Controllers\Web\LivePlayerController::class, 'show'])
    ->name('live.player');
Route::get('/haqqimizda', [StorefrontController::class, 'about'])->name('storefront.about');
Route::get('/qaydalar', [StorefrontController::class, 'terms'])->name('storefront.terms');
Route::get('/elaqe', [StorefrontController::class, 'contact'])->name('storefront.contact');
Route::get('/robots.txt', [StorefrontController::class, 'robots'])->name('storefront.robots');
Route::get('/sitemap.xml', [StorefrontController::class, 'sitemap'])->name('storefront.sitemap');
Route::get('/.well-known/assetlinks.json', [AppLinkController::class, 'assetLinks'])->name('app-links.assetlinks');
Route::get('/apple-app-site-association', [AppLinkController::class, 'appleAssociation'])->name('app-links.apple');
Route::get('/.well-known/apple-app-site-association', [AppLinkController::class, 'appleAssociation'])->name('app-links.apple-well-known');

Route::get('/home', [\App\Http\Controllers\HomeController::class, 'index'])->name('home.legacy');
Route::get('/privacy-and-policy', [\Modules\HelpAndPolicy\Http\Controllers\LegalTermController::class,'privacyAndPolicy'])->name('privacy-and-policy');
