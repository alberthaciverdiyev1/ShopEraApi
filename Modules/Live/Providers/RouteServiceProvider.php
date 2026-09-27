<?php

namespace Modules\Live\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')
            ->group(module_path('Live', 'Routes/api.php'));
    }
}
