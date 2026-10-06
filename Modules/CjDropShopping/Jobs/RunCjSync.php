<?php

namespace Modules\CjDropShopping\Jobs;

use App\Jobs\Concerns\TenantAware;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\CjDropShopping\Services\DCategoryService;
use Modules\CjDropShopping\Services\DProductService;

/**
 * Runs a CJ Dropshipping sync off the HTTP request so a slow provider call can
 * never block the admin (and the single-threaded dev server). The result is
 * cached per tenant for the admin page to display on the next render.
 */
class RunCjSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAware;

    public int $timeout = 3600;

    public int $tries = 1;

    /**
     * @param  string  $resource  categories|products|all
     */
    public function __construct(
        public readonly string $resource,
        public readonly bool $translate = false,
    ) {
        $this->captureTenant();
    }

    public function handle(): void
    {
        $results = [];

        foreach ($this->resources() as $key) {
            $result = $key === 'categories'
                ? app(DCategoryService::class)->sync($this->translate)
                : app(DProductService::class)->sync($this->translate);

            // The id map can hold hundreds of entries; not needed for the UI.
            unset($result['map']);

            $results[$key] = $result;
        }

        Cache::put($this->resultKey(), [
            'resource' => $this->resource,
            'results' => $results,
            'at' => now()->toIso8601String(),
        ], now()->addHours(6));
    }

    /** @return array<int,string> */
    private function resources(): array
    {
        return $this->resource === 'all' ? ['categories', 'products'] : [$this->resource];
    }

    public static function resultKey(): string
    {
        return TenantContext::cacheKey('cjdropshopping:last_result');
    }
}
