<?php

use App\Http\Controllers\Storefront\HomeController;
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
