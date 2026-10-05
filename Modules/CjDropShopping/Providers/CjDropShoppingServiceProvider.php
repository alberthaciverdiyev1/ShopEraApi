<?php

namespace Modules\CjDropShopping\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\CjDropShopping\Services\DAuthService;
use Modules\CjDropShopping\Services\DBrandService;
use Modules\CjDropShopping\Services\DCategoryService;
use Modules\CjDropShopping\Services\DFreightService;
use Modules\CjDropShopping\Services\DOrderService;
use Modules\CjDropShopping\Services\DPaymentService;
use Modules\CjDropShopping\Services\DProductService;
use Modules\CjDropShopping\Services\DShopService;
use Modules\CjDropShopping\Services\DTrackingService;
use Modules\CjDropShopping\Services\DUserService;
use Modules\CjDropShopping\Services\DWarehouseService;
use Nwidart\Modules\Traits\PathNamespace;

class CjDropShoppingServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'CjDropShopping';

    protected string $nameLower = 'cjdropshopping';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
    }

    public function register(): void
    {
        // The module exposes services only (no HTTP routes). Inject the one you
        // need wherever the integration is used.
        $this->app->singleton(DAuthService::class);
        $this->app->singleton(DProductService::class);
        $this->app->singleton(DCategoryService::class);
        $this->app->singleton(DBrandService::class);
        $this->app->singleton(DFreightService::class);
        $this->app->singleton(DTrackingService::class);
        $this->app->singleton(DOrderService::class);
        $this->app->singleton(DPaymentService::class);
        $this->app->singleton(DUserService::class);
        $this->app->singleton(DShopService::class);
        $this->app->singleton(DWarehouseService::class);
    }

    /**
     * Publish/merge the module config so `config('cjdropshopping.*')` works.
     */
    protected function registerConfig(): void
    {
        $path = module_path($this->name, 'Config/config.php');

        if (! is_file($path)) {
            return;
        }

        $this->publishes([$path => config_path('cjdropshopping.php')], 'config');

        config(['cjdropshopping' => array_replace_recursive(
            config('cjdropshopping', []),
            require $path,
        )]);
    }
}
