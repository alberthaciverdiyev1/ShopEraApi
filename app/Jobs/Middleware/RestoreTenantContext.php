<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Restores the tenant database connection a job was dispatched under, runs the
 * job, then puts the worker's default (central) connection back.
 */
class RestoreTenantContext
{
    public function handle(object $job, Closure $next): mixed
    {
        $database = $job->tenantDatabase ?? null;

        if (! is_string($database) || $database === '') {
            return $next($job);
        }

        $previous = config('database.default');

        config([
            'database.connections.tenant.database' => $database,
            'tenant.current_host' => $job->tenantHost ?? null,
            'tenant.current_database' => $database,
            'tenant.current_storage_root' => $job->tenantStorageRoot ?? null,
        ]);
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');

        try {
            return $next($job);
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('tenant');
            config([
                'tenant.current_host' => null,
                'tenant.current_database' => null,
                'tenant.current_storage_root' => null,
            ]);
        }
    }
}
