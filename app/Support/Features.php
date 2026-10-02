<?php

namespace App\Support;

use App\Models\TenantEntitlement;
use Illuminate\Support\Facades\Log;

/**
 * Reads the instance's feature entitlements mirrored from Manager.Snaker into
 * the tenant's own database. Until a sync has ever happened, features are
 * treated as enabled so a fresh install is never locked out (fail-open).
 */
class Features
{
    /** Numeric limits defined in Manager.Snaker's feature catalogue. */
    public const LIMIT_KEYS = [
        'max_products',
        'max_categories',
        'max_staff',
        'max_orders',
        'storage_gb',
    ];

    private static bool $warned = false;

    /** @var array<string,array<string,bool>|null> request-scoped memo, keyed by tenant */
    private static array $memo = [];

    /** @var array<string,array<string,int|float|null>> request-scoped memo, keyed by tenant */
    private static array $limitMemo = [];

    /** @return array<string,bool>|null Null when this instance was never synced. */
    public static function all(): ?array
    {
        $key = TenantContext::cacheKey('features');

        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        try {
            $rows = TenantEntitlement::query()->get();
        } catch (\Throwable $e) {
            // Table missing (fresh install / unmigrated) — stay fail-open, but
            // make the misconfiguration visible instead of silently unlocking
            // every feature for both free and paid tenants.
            self::warnOnce($e);

            return self::$memo[$key] = null;
        }

        if ($rows->isEmpty()) {
            return self::$memo[$key] = null;
        }

        return self::$memo[$key] = $rows->mapWithKeys(fn (TenantEntitlement $row) => [
            $row->key => self::truthy($row->value),
        ])->all();
    }

    /** Drop the request-scoped memo (e.g. right after a sync updates entitlements). */
    public static function flush(): void
    {
        self::$memo = [];
        self::$limitMemo = [];
    }

    /**
     * Numeric plan limits (max_products, max_categories, max_staff,
     * max_orders, storage_gb) as mirrored from Manager.Snaker.
     *
     * A null value means "unlimited" — either the limit was never synced
     * (fail-open) or the plan leaves that dimension uncapped.
     *
     * @return array<string,int|float|null>
     */
    public static function limits(): array
    {
        $key = TenantContext::cacheKey('limits');

        if (array_key_exists($key, self::$limitMemo)) {
            return self::$limitMemo[$key];
        }

        try {
            $rows = TenantEntitlement::query()
                ->whereIn('key', self::LIMIT_KEYS)
                ->pluck('value', 'key');
        } catch (\Throwable $e) {
            self::warnOnce($e);

            return self::$limitMemo[$key] = array_fill_keys(self::LIMIT_KEYS, null);
        }

        $limits = [];
        foreach (self::LIMIT_KEYS as $limitKey) {
            $value = $rows[$limitKey] ?? null;
            $limits[$limitKey] = is_numeric($value) ? $value + 0 : null;
        }

        return self::$limitMemo[$key] = $limits;
    }

    public static function enabled(string $key, bool $default = true): bool
    {
        $flags = self::all();

        if ($flags === null) {
            return $default;
        }

        return (bool) ($flags[$key] ?? $default);
    }

    public static function any(array $keys, bool $default = true): bool
    {
        foreach ($keys as $key) {
            if (self::enabled($key, $default)) {
                return true;
            }
        }

        return false;
    }

    public static function limit(string $key): ?int
    {
        try {
            $value = TenantEntitlement::query()->where('key', $key)->value('value');
        } catch (\Throwable) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private static function truthy(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on', 'enabled'], true);
    }

    private static function warnOnce(\Throwable $e): void
    {
        if (self::$warned) {
            return;
        }

        self::$warned = true;

        Log::warning('Tenant entitlements unavailable; features are fail-open.', [
            'error' => $e->getMessage(),
        ]);
    }
}
