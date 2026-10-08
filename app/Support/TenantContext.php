<?php

namespace App\Support;

/**
 * Single-database deployment: there is no per-host tenant any more, so every
 * helper is a pass-through. Kept so storage-path and cache-key callers do not
 * have to special-case the multi-tenant era this class used to implement.
 */
class TenantContext
{
    public static function host(): ?string
    {
        return null;
    }

    public static function database(): ?string
    {
        return null;
    }

    public static function active(): bool
    {
        return false;
    }

    public static function cacheKey(string $key): string
    {
        return $key;
    }

    public static function storagePath(string $path): string
    {
        return ltrim($path, '/');
    }

    public static function storageSlug(): ?string
    {
        return null;
    }
}
