<?php

namespace App\Support;

class TenantContext
{
    public static function host(): ?string
    {
        $host = config('tenant.current_host');

        return is_string($host) && $host !== '' ? $host : null;
    }

    public static function database(): ?string
    {
        $database = config('tenant.current_database');

        return is_string($database) && $database !== '' ? $database : null;
    }

    public static function active(): bool
    {
        return self::database() !== null;
    }

    public static function cacheKey(string $key): string
    {
        $tenant = self::database() ?: self::host();

        return $tenant ? "tenant:{$tenant}:{$key}" : $key;
    }

    public static function storagePath(string $path): string
    {
        $path = ltrim($path, '/');
        $tenant = self::storageSlug();

        if (! $tenant || str_starts_with($path, $tenant.'/') || str_starts_with($path, 'tenants/')) {
            return $path;
        }

        return $path === '' ? "{$tenant}/" : "{$tenant}/{$path}";
    }

    public static function storageSlug(): ?string
    {
        $storageRoot = config('tenant.current_storage_root');
        if (is_string($storageRoot) && $storageRoot !== '') {
            return trim($storageRoot, '/');
        }

        // Prefer the tenant database: a tenant can be reached through several
        // domains (custom domain + subdomain), but they all resolve to the same
        // database, so files stay in one folder regardless of the domain used.
        $database = self::database();
        if ($database) {
            $slug = preg_replace('/^shopera_/', '', $database);
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $slug)), '-');

            if ($slug !== '') {
                return $slug;
            }
        }

        $host = self::host();
        if (! $host) {
            return null;
        }

        $host = strtolower(preg_replace('/:\d+$/', '', $host));
        $parts = array_values(array_filter(explode('.', $host)));
        $slug = count($parts) > 2 && $parts[0] !== 'www' ? $parts[0] : $host;
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $slug), '-');

        return $slug !== '' ? $slug : null;
    }
}
