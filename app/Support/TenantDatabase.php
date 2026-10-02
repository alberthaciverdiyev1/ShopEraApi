<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for tenant database names and the two control-plane
 * cache keys (host => database map, database existence). One codebase serves
 * every tenant (free and paid alike); each tenant has its own database.
 */
class TenantDatabase
{
    /**
     * Derive the tenant database name from a host. Must stay identical for
     * ResolveTenant, tenant:provision and manager:map so a host always maps
     * to the same database.
     */
    public static function nameFor(string $host): string
    {
        $slug = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($host)), '_');

        return $slug !== '' ? 'shopera_'.substr($slug, 0, 40) : 'shopera';
    }

    /**
     * The connection used to reach the central catalog (pg_database). The
     * default connection is swapped to "tenant" while serving a request, so we
     * must fall back to pgsql there instead of resolving "tenant".
     */
    public static function centralConnection(): string
    {
        $default = (string) config('database.default');

        return $default === 'tenant' ? 'pgsql' : $default;
    }

    /**
     * Cache repository for tenant metadata. Uses a store that does not depend
     * on the (per-tenant) database connection so ResolveTenant can read it
     * before the connection is switched and manager:map can write it while
     * the connection is switched.
     */
    public static function cache(): CacheRepository
    {
        $store = config('tenant.cache_store');

        return $store ? Cache::store($store) : Cache::store();
    }

    public static function forgetExistence(?string $database): void
    {
        if (is_string($database) && $database !== '') {
            self::cache()->forget("tenant_exists:{$database}");
        }
    }

    /**
     * Unique tenant databases with their storage root. Several hosts (custom
     * domain + subdomain) can point at one database, so they collapse here.
     *
     * @return array<string,?string> database => storage_root
     */
    public static function databases(): array
    {
        $map = (array) self::cache()->get(config('tenant.map_cache'), []);
        $metadata = (array) self::cache()->get(config('tenant.metadata_cache'), []);
        $databases = [];

        foreach ($map as $host => $database) {
            if (! is_string($database) || $database === '' || isset($databases[$database])) {
                continue;
            }

            $databases[$database] = $metadata[$host]['storage_root'] ?? null;
        }

        return $databases;
    }

    /**
     * One representative host per database. A tenant reached through several
     * domains must be synced/reported once, not once per domain.
     *
     * @return array<string,string> host => database
     */
    public static function uniqueHosts(): array
    {
        $map = (array) self::cache()->get(config('tenant.map_cache'), []);
        $hosts = [];

        foreach ($map as $host => $database) {
            if (! is_string($database) || $database === '' || in_array($database, $hosts, true)) {
                continue;
            }

            $hosts[$host] = $database;
        }

        return $hosts;
    }

    /**
     * Runs $callback once per tenant database, with the connection swapped.
     * When no tenant map exists (single self-hosted deployment) it runs once
     * with $database = null on the current (central) connection.
     *
     * @param  callable(?string,?string):void  $callback
     */
    public static function each(callable $callback): void
    {
        $databases = self::databases();

        if ($databases === []) {
            $callback(null, null);

            return;
        }

        $previous = config('database.default');

        try {
            foreach ($databases as $database => $storageRoot) {
                config([
                    'database.connections.tenant.database' => $database,
                    'tenant.current_host' => null,
                    'tenant.current_database' => $database,
                    'tenant.current_storage_root' => $storageRoot,
                ]);
                DB::purge('tenant');
                DB::setDefaultConnection('tenant');

                $callback($database, $storageRoot);
            }
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

    /**
     * Positive results only are cached; a missing database is never cached
     * negatively so a freshly provisioned tenant is visible immediately.
     */
    public static function exists(string $database): bool
    {
        if ($database === '') {
            return false;
        }

        $key = "tenant_exists:{$database}";
        $cache = self::cache();

        if ($cache->get($key)) {
            return true;
        }

        try {
            $exists = DB::connection(self::centralConnection())
                ->table('pg_database')
                ->where('datname', $database)
                ->exists();
        } catch (\Throwable) {
            return false;
        }

        if ($exists) {
            $cache->put($key, true, 300);
        }

        return $exists;
    }
}
