<?php

namespace Modules\Manager\Console;

use App\Support\TenantDatabase;
use Illuminate\Console\Command;
use Modules\Manager\Entities\SiteOwner;

/**
 * Rebuilds the tenant host -> database map (and per-host metadata) from the
 * control database. Replaces the map-building half of the old manager:sync.
 */
class MapCommand extends Command
{
    protected $signature = 'manager:map';

    protected $description = 'Rebuild the tenant host->database map from the control DB';

    public function handle(): int
    {
        $map = [];
        $metadata = [];

        SiteOwner::query()->with('domains')->chunkById(200, function ($owners) use (&$map, &$metadata) {
            foreach ($owners as $owner) {
                $database = $owner->db_name ?: $owner->suggestedDbName();

                if (! $database) {
                    continue;
                }

                foreach ($owner->domains as $domain) {
                    $host = strtolower(trim((string) $domain->host));

                    if ($host === '') {
                        continue;
                    }

                    $map[$host] = $database;
                    $metadata[$host] = [
                        'host' => $host,
                        'database' => $database,
                        'tenant_slug' => $owner->tenantSlug(),
                        'storage_root' => $owner->storageRoot(),
                    ];
                }
            }
        });

        $cache = TenantDatabase::cache();
        $cache->put(config('tenant.map_cache'), $map, 3600);
        $cache->put(config('tenant.metadata_cache'), $metadata, 3600);

        $this->info('Tenant map updated: '.count($map).' host(s).');

        return self::SUCCESS;
    }
}
