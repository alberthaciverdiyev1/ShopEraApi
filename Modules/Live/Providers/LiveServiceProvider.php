<?php

namespace Modules\Live\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Live\Console\RefreshLiveViewerCounts;
use Modules\Live\Jobs\AttributeLiveOrderJob;
use Modules\Order\Http\Entities\Order;

class LiveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Live', 'Database/Migrations'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                RefreshLiveViewerCounts::class,
            ]);
        }

        $this->attributeOrdersToStreams();
    }

    public function register(): void
    {
        // A relative path, not module_path(): that helper resolves through the
        // module repository, which reaches for the cache service before it has
        // been registered and brings the whole console down with
        // "Target class [cache] does not exist".
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'live');

        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Checkout is not touched. The hook only queues a background job, and even
     * that is wrapped: a live-shopping statistic must never be the reason a
     * customer's order fails.
     */
    private function attributeOrdersToStreams(): void
    {
        Order::created(function (Order $order): void {
            try {
                AttributeLiveOrderJob::dispatch($order->id)->afterCommit();
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }
}
