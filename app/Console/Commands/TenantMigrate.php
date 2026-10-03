<?php

namespace App\Console\Commands;

use App\Support\TenantDatabase;
use Illuminate\Console\Command;

/**
 * Runs migrations on every tenant database from the Manager host→db map.
 * Falls back to the current connection when the instance has no tenant map
 * (single self-hosted deployment).
 */
class TenantMigrate extends Command
{
    protected $signature = 'tenant:migrate {--force : Run migrations in production}';

    protected $description = 'Run migrations for every tenant database';

    public function handle(): int
    {
        $count = 0;
        $failed = 0;

        TenantDatabase::each(function (?string $database) use (&$count, &$failed): void {
            $label = $database ?: (string) config('database.connections.tenant.database');
            $this->info("Migrating tenant: {$label}");

            $exit = $this->call('migrate', [
                '--database' => 'tenant',
                '--force' => true,
            ]);

            $exit === 0 ? $count++ : $failed++;
        });

        $this->info("tenant:migrate complete: {$count} database(s) migrated".($failed ? ", {$failed} failed" : '').'.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
