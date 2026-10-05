<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Modules\Product\Entities\Product;

/**
 * Current consumption for the plan limits mirrored from Manager.Snaker.
 * Keys match Features::LIMIT_KEYS so usage and limit line up one-to-one.
 */
class PlanUsage
{
    public const array KEYS = ['max_products', 'storage_mb'];

    /** @return array<string,int|float> */
    public static function all(): array
    {
        return array_combine(self::KEYS, array_map(fn ($k) => self::value($k), self::KEYS));
    }

    /** Usage for a single limit key (avoids scanning storage for count checks). */
    public static function value(string $key): int|float
    {
        return match ($key) {
            'max_products' => Product::query()->count(),
            'storage_mb' => round(self::storageBytes() / 1048576, 2),
            default => 0,
        };
    }

    /** Precise storage usage in bytes (used for limit checks). */
    public static function storageBytes(): int
    {
        $bytes = 0;

        try {
            $prefix = TenantContext::active() ? TenantContext::storagePath('') : '';

            foreach (Storage::disk('public')->allFiles($prefix) as $file) {
                $bytes += (int) Storage::disk('public')->size($file);
            }
        } catch (\Throwable) {
            // Storage not configured / unreadable — report 0 rather than fail.
        }

        return $bytes;
    }
}
