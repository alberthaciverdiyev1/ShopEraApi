<?php

namespace App\Support;

use App\Models\TenantSubscription;
use Illuminate\Support\Facades\Log;

/**
 * Reads the instance subscription snapshot mirrored from Manager.Snaker into
 * the tenant's own database. Unknown/unsynced → treated as usable (fail-open).
 */
class Subscription
{
    private static bool $warned = false;

    /** @var array<string,?TenantSubscription> request-scoped memo, keyed by tenant */
    private static array $memo = [];

    public static function model(): ?TenantSubscription
    {
        $key = TenantContext::cacheKey('subscription');

        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        try {
            return self::$memo[$key] = TenantSubscription::query()->latest('synced_at')->latest('id')->first();
        } catch (\Throwable $e) {
            // Table missing (fresh install / unmigrated) — stay fail-open, but
            // surface the problem so a broken sync does not silently disable
            // subscription gating for every tenant.
            if (! self::$warned) {
                self::$warned = true;
                Log::warning('Tenant subscription unavailable; treating as usable.', [
                    'error' => $e->getMessage(),
                ]);
            }

            return self::$memo[$key] = null;
        }
    }

    /** Drop the request-scoped memo (e.g. right after a sync updates the plan). */
    public static function flush(): void
    {
        self::$memo = [];
    }

    public static function data(): array
    {
        $subscription = self::model();

        if (! $subscription) {
            return [];
        }

        return [
            'status' => $subscription->status,
            'plan' => $subscription->plan_name,
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'usable' => (bool) $subscription->usable,
        ];
    }

    public static function status(): ?string
    {
        return self::data()['status'] ?? null;
    }

    public static function plan(): ?string
    {
        return self::data()['plan'] ?? null;
    }

    public static function endsAt(): ?string
    {
        return self::data()['ends_at'] ?? null;
    }

    public static function usable(): bool
    {
        return (bool) (self::data()['usable'] ?? true);
    }

    public static function isPastDue(): bool
    {
        return self::status() === 'past_due';
    }

    /** Blocked = expired / cancelled / suspended (or non-usable). */
    public static function isBlocked(): bool
    {
        return self::data() !== [] && ! self::usable();
    }
}
