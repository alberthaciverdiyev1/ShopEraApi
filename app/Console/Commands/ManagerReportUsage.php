<?php

namespace App\Console\Commands;

use App\Support\ManagerClient;
use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Category\Entities\Category;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

/** Pushes current usage counters to Manager.Snaker (limits dashboard). */
class ManagerReportUsage extends Command
{
    protected $signature = 'manager:report-usage';

    protected $description = 'Report usage (products, categories, staff, storage) to Manager.Snaker';

    public function handle(): int
    {
        if (empty(config('services.manager.url'))) {
            $this->warn('MANAGER_URL is not configured.');

            return self::SUCCESS;
        }

        $targets = $this->targets();
        if ($targets === []) {
            $this->warn('No tenants to report.');

            return self::SUCCESS;
        }

        $previous = config('database.default');
        $reported = 0;

        try {
            $metadata = (array) TenantDatabase::cache()->get(config('tenant.metadata_cache'), []);

            foreach ($targets as $host => $database) {
                try {
                    if (! TenantDatabase::exists($database)) {
                        $this->warn("Skipping {$host}: database {$database} does not exist yet.");

                        continue;
                    }

                    config(['database.connections.tenant.database' => $database]);
                    config([
                        'tenant.current_host' => $host,
                        'tenant.current_database' => $database,
                        'tenant.current_storage_root' => $metadata[$host]['storage_root'] ?? null,
                    ]);
                    DB::purge('tenant');
                    DB::setDefaultConnection('tenant');

                    if ($this->reportFor($host)) {
                        $reported++;
                    }
                } catch (\Throwable $e) {
                    $this->error("Usage report failed for {$host}: {$e->getMessage()}");
                }
            }
        } finally {
            // Never leak the last tenant connection into the rest of the process.
            DB::setDefaultConnection($previous);
            DB::purge('tenant');
        }

        $this->info("Usage reported to Manager for {$reported} tenant(s).");

        return self::SUCCESS;
    }

    private function reportFor(string $host): bool
    {
        $storageBytes = 0;
        try {
            $prefix = config('tenant.current_database') ? TenantContext::storagePath('') : '';
            foreach (Storage::disk('public')->allFiles($prefix) as $file) {
                $storageBytes += (int) Storage::disk('public')->size($file);
            }
        } catch (\Throwable) {
            // ignore
        }

        $response = ManagerClient::post('/api/v1/usage', [
            'products' => Product::query()->count(),
            'categories' => Category::query()->count(),
            'staff' => User::query()->whereHas('roles', fn ($q) => $q->where('name', '!=', 'user'))->count(),
            'storage_gb' => round($storageBytes / 1073741824, 2),
        ], $host !== '' ? $host : null);

        if (! $response || ! $response->ok()) {
            $this->error("Usage report failed for {$host}.");

            return false;
        }

        return true;
    }

    /** @return array<string,string> host => database */
    private function targets(): array
    {
        // One report per tenant database, even if it has several domains.
        $hosts = TenantDatabase::uniqueHosts();

        if ($hosts !== []) {
            return $hosts;
        }

        $host = (string) (config('services.manager.site_host') ?: gethostname());

        return [$host => TenantDatabase::nameFor($host)];
    }
}
