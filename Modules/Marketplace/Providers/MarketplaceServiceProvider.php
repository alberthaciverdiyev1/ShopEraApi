<?php

namespace Modules\Marketplace\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Marketplace\Console\ExpireListings;
use Modules\Marketplace\Console\ExpirePromotions;
use Modules\Marketplace\Console\ImportDresssoData;
use Modules\Marketplace\Console\RecalcVendorRatings;
use Nwidart\Modules\Traits\PathNamespace;

class MarketplaceServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Marketplace';

    protected string $nameLower = 'marketplace';

    public function boot(): void
    {
        $this->registerCommands();
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
    }

    protected function registerCommands(): void
    {
        $this->commands([
            ExpireListings::class,
            ExpirePromotions::class,
            ImportDresssoData::class,
            RecalcVendorRatings::class,
        ]);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
