<?php

namespace Modules\Marketplace\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class MarketplaceServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Marketplace';

    protected string $nameLower = 'marketplace';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
