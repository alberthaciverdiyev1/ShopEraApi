<?php

namespace App\Providers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNAdapter;
use PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Saytın başlığı və altlığı hər səhifədə eyni məlumatı istəyir.
         * Əvvəllər onu hər metod ayrıca ötürürdü — yeni səhifə əlavə edən
         * kimi layout "Undefined variable $settings" verirdi.
         */
        View::composer('storefront.layout', function ($view) {
            $view->with([
                'settings' => Cache::remember(
                    'storefront:settings',
                    600,
                    fn () => \Modules\Setting\Http\Entities\Setting::query()->first(),
                ),
                'downloadLinks' => [
                    'ios' => config('app.app_store_url', '#'),
                    'android' => config('app.play_store_url', '#'),
                ],
            ]);
        });

//        if ($this->app->environment('production')) {
//            URL::forceScheme('https');
//        }

        if (app()->environment('production') || app()->environment('staging')) {
            URL::forceScheme('https');
        }

        Storage::extend('bunnycdn', function ($app, $config) {
            $adapter = new BunnyCDNAdapter(
                new BunnyCDNClient(
                    $config['storage_zone'],
                    $config['api_key'],
                    $config['region']
                ),
                $config['pull_zone']
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
