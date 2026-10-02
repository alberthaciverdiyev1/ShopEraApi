<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\ThemeController;
use App\Support\EntitlementStore;
use App\Support\ManagerClient;
use App\Support\TenantDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pulls subscriptions, entitlements and the theme from Manager.ShopEra and
 * mirrors them into each tenant's own database. A single codebase serves every
 * host — free and paid plans alike — each on its own database.
 */
class ManagerSync extends Command
{
    protected $signature = 'manager:sync {--host= : Sync a single host only}';

    protected $description = 'Sync subscriptions, entitlements and theme from Manager.ShopEra';

    /** @var array<string,array{storage_root?:?string}> host => metadata */
    private array $metadata = [];

    public function handle(): int
    {
        if (empty(config('services.manager.url'))) {
            $this->warn('MANAGER_URL is not configured.');

            return self::SUCCESS;
        }

        $targets = $this->targets();

        if ($targets === []) {
            $this->warn('No tenants to sync.');

            return self::SUCCESS;
        }

        $previous = config('database.default');
        $synced = 0;

        try {
            foreach ($targets as $host => $database) {
                try {
                    if (! TenantDatabase::exists($database)) {
                        $this->warn("Skipping {$host}: database {$database} does not exist yet.");

                        continue;
                    }

                    $response = ManagerClient::get('/api/v1/entitlements', [], $host !== '' ? $host : null);

                    if (! $response || ! $response->ok()) {
                        $this->error("Manager unreachable for {$host}.");

                        continue;
                    }

                    $data = $response->json('data') ?? [];

                    config(['database.connections.tenant.database' => $database]);
                    config([
                        'tenant.current_host' => $host,
                        'tenant.current_database' => $database,
                        'tenant.current_storage_root' => $this->metadata[$host]['storage_root'] ?? null,
                    ]);
                    DB::purge('tenant');
                    DB::setDefaultConnection('tenant');

                    EntitlementStore::persist($data, $host);
                    app(ThemeController::class)->storePalette($data['theme'] ?? []);
                    TenantDatabase::forgetExistence($database);
                    $synced++;
                } catch (\Throwable $e) {
                    $this->error("Sync failed for {$host}: {$e->getMessage()}");
                }
            }
        } finally {
            // Never leak the last tenant connection into the rest of the process.
            DB::setDefaultConnection($previous);
            DB::purge('tenant');
        }

        $this->info("Manager sync complete: {$synced} tenant(s).");

        return self::SUCCESS;
    }

    /** @return array<string,string> host => database */
    private function targets(): array
    {
        $map = $this->refreshMap();

        if ($only = (string) $this->option('host')) {
            return [$only => $map[$only] ?? TenantDatabase::nameFor($only)];
        }

        // Collapse custom domains: sync each tenant database once.
        return TenantDatabase::uniqueHosts();
    }

    /**
     * Refresh the host => database map (and metadata) from Manager. Falls back
     * to the last known map, then to a single self-hosted tenant.
     *
     * @return array<string,string>
     */
    private function refreshMap(): array
    {
        $rows = $this->tenantRows();

        if ($rows !== []) {
            $map = [];
            $metadata = [];

            // A tenant can expose several hosts (custom domain + subdomain).
            // They must all point at the same database and storage root.
            foreach ($rows as $row) {
                foreach ($row['hosts'] as $tenantHost) {
                    $map[$tenantHost] = $row['database'];
                    $metadata[$tenantHost] = [
                        'host' => $tenantHost,
                        'database' => $row['database'],
                        'tenant_slug' => $row['tenant_slug'],
                        'storage_root' => $row['storage_root'],
                    ];
                }
            }

            $this->metadata = $metadata;

            $cache = TenantDatabase::cache();
            $cache->put(config('tenant.map_cache'), $map, 3600);
            $cache->put(config('tenant.metadata_cache'), $metadata, 3600);

            return $map;
        }

        $map = (array) TenantDatabase::cache()->get(config('tenant.map_cache'), []);
        $this->metadata = (array) TenantDatabase::cache()->get(config('tenant.metadata_cache'), []);

        if ($map !== []) {
            return $map;
        }

        // Last resort: a single self-hosted deployment.
        $host = (string) (config('services.manager.site_host') ?: gethostname());

        return [$host => TenantDatabase::nameFor($host)];
    }

    /**
     * Each row may carry the primary "host" plus optional "hosts"/"domains"
     * arrays for custom domains pointing at the same tenant.
     *
     * @return array<int,array{hosts:array<int,string>,database:string,tenant_slug:?string,storage_root:?string}>
     */
    private function tenantRows(): array
    {
        $response = ManagerClient::get('/api/v1/tenants');

        if (! $response || ! $response->ok()) {
            return [];
        }

        return collect($response->json('data.tenants') ?? [])
            ->map(function ($t) {
                $hosts = array_merge(
                    [(string) ($t['host'] ?? '')],
                    (array) ($t['hosts'] ?? []),
                    (array) ($t['domains'] ?? []),
                );

                return [
                    'hosts' => array_values(array_unique(array_filter(array_map(
                        fn ($h) => strtolower(trim((string) $h)),
                        $hosts,
                    )))),
                    'database' => (string) ($t['database'] ?? ''),
                    'tenant_slug' => $t['tenant_slug'] ?? null,
                    'storage_root' => $t['storage_root'] ?? null,
                ];
            })
            ->filter(fn ($t) => $t['hosts'] !== [] && $t['database'] !== '')
            ->values()
            ->all();
    }
}
