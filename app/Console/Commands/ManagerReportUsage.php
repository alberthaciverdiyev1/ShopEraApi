<?php

namespace App\Console\Commands;

use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Category\Entities\Category;
use Modules\Manager\Entities\SiteOwner;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

/** Stores tenant usage counters on the control-DB site owners (limits view). */
class ManagerReportUsage extends Command
{
    protected $signature = 'manager:report-usage';

    protected $description = 'Store tenant usage (products, categories, staff, storage) on the control DB';

    public function handle(): int
    {
        $targets = TenantDatabase::uniqueHosts();

        if ($targets === []) {
            $this->warn('No tenants to report.');

            return self::SUCCESS;
        }

        $previous = config('database.default');
        $metadata = (array) TenantDatabase::cache()->get(config('tenant.metadata_cache'), []);
        $reported = 0;

        try {
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

                    if ($this->reportFor($host, $database)) {
                        $reported++;
                    }
                } catch (\Throwable $e) {
                    $this->error("Usage report failed for {$host}: {$e->getMessage()}");
                }
            }
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('tenant');
        }

        $this->info("Usage stored for {$reported} tenant(s).");

        return self::SUCCESS;
    }

    private function reportFor(string $host, string $database): bool
    {
        $storageBytes = 0;
        try {
            $prefix = $database ? TenantContext::storagePath('') : '';
            foreach (Storage::disk('public')->allFiles($prefix) as $file) {
                $storageBytes += (int) Storage::disk('public')->size($file);
            }
        } catch (\Throwable) {
            // ignore
        }

        $usage = [
            'usage_products' => Product::query()->count(),
            'usage_categories' => Category::query()->count(),
            'usage_staff' => User::query()->whereHas('roles', fn ($q) => $q->where('name', '!=', 'user'))->count(),
            'usage_storage_gb' => round($storageBytes / 1073741824, 2),
            'usage_reported_at' => now(),
        ];

        // Write on the control connection (models above already run on the
        // tenant connection via the default connection switch).
        $owner = SiteOwner::query()->byHost($host)->first()
            ?? SiteOwner::query()->where('db_name', $database)->first();

        if (! $owner) {
            $this->warn("No site owner for {$host}.");

            return false;
        }

        $owner->update($usage);

        return true;
    }
}
