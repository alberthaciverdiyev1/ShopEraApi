<?php

namespace Modules\Marketplace\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Marketplace';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/Routes/api.php'));
    }
}
