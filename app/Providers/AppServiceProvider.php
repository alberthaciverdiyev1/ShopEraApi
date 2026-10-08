<?php

namespace App\Providers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use App\Support\TenantContext;
use Modules\Setting\Entities\Setting;
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
        $this->configureRateLimiting();

        /*
         * Saytın başlığı və altlığı hər səhifədə eyni məlumatı istəyir.
         * Əvvəllər onu hər metod ayrıca ötürürdü — yeni səhifə əlavə edən
         * kimi layout "Undefined variable $settings" verirdi.
         */
        View::composer('storefront.layout', function ($view) {
            $view->with([
                'settings' => Cache::remember(
                    TenantContext::cacheKey('storefront:settings'),
                    600,
                    fn () => Setting::query()->first(),
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
    /** Throttle the abuse-prone public/customer endpoints. */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('guest-listings', fn ($request) => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('listings', fn ($request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('reports', fn ($request) => Limit::perHour(5)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('chat', fn ($request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('otp', fn ($request) => Limit::perMinute(5)->by($request->ip()));
    }
}
