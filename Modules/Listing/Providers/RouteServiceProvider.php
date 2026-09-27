<?php

namespace Modules\Listing\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')
            ->group(module_path('Listing', 'Routes/api.php'));
    }
}
