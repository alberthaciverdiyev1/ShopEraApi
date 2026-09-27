<?php

namespace Modules\Listing\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Listing\Console\ExpireListings;
use Modules\Listing\Console\RefreshListingValues;
use Modules\Listing\Console\SeedListingCatalogue;

class ListingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Listing', 'Database/Migrations'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireListings::class,
                RefreshListingValues::class,
                SeedListingCatalogue::class,
            ]);
        }
    }

    public function register(): void
    {
        // A relative path, not module_path(): that helper resolves through the
        // module repository, which reaches for the cache service before it is
        // registered and brings the console down with "Target class [cache]
        // does not exist". The Live module hit this first.
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'listing');

        $this->app->register(RouteServiceProvider::class);
    }
}
