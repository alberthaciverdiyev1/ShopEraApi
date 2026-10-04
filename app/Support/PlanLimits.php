<?php

namespace App\Support;

/**
 * Enforces the numeric plan limits mirrored from Manager.Snaker. A null or
 * negative limit means "unlimited" (fail-open, like Features::enabled).
 */
class PlanLimits
{
    /** Would adding $adding to the current usage exceed the limit? */
    public static function reached(string $key, int $adding = 0): bool
    {
        $limit = Features::limit($key);

        if ($limit === null || $limit < 0) {
            return false;
        }

        $used = (int) PlanUsage::value($key);

        return ($used + $adding) > $limit;
    }

    /** Would $incomingBytes more data exceed the storage limit? */
    public static function storageFull(int $incomingBytes): bool
    {
        $limitMb = Features::limit('storage_mb');

        if ($limitMb === null || $limitMb < 0) {
            return false;
        }

        $limitBytes = (int) round($limitMb * 1048576);

        return (PlanUsage::storageBytes() + $incomingBytes) > $limitBytes;
    }
}
