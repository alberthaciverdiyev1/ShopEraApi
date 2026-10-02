<?php

namespace App\Console\Commands;

use App\Support\TenantDatabase;
use Illuminate\Console\Command;

/**
 * Shows the host → database map Manager has pushed to this instance, whether
 * each database exists on the server, and where its files live.
 */
class TenantList extends Command
{
    protected $signature = 'tenant:list {--json : Output raw JSON}';

    protected $description = 'List tenants (host, database, existence, storage root)';

    public function handle(): int
    {
        $map = (array) TenantDatabase::cache()->get(config('tenant.map_cache'), []);
        $metadata = (array) TenantDatabase::cache()->get(config('tenant.metadata_cache'), []);

        if ($map === []) {
            $this->warn('No tenants in the map. Run "php artisan manager:map" first.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($map as $host => $database) {
            $rows[] = [
                'host' => $host,
                'database' => $database,
                'exists' => TenantDatabase::exists($database) ? 'yes' : 'no',
                'storage_root' => $metadata[$host]['storage_root'] ?? '-',
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(['Host', 'Database', 'Exists', 'Storage root'], $rows);

        return self::SUCCESS;
    }
}
