<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Modules\Category\Entities\Category;
use Modules\Order\Entities\Order;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

/**
 * Current consumption for the plan limits mirrored from Manager.Snaker.
 * Keys match Features::LIMIT_KEYS so usage and limit line up one-to-one.
 */
class PlanUsage
{
    public const KEYS = ['max_products', 'max_categories', 'max_staff', 'max_orders', 'storage_gb'];

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
            'max_categories' => Category::query()->count(),
            'max_staff' => self::staffCount(),
            // max_orders is a monthly cap, so usage counts the current month.
            'max_orders' => Order::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            'storage_gb' => self::storageGb(),
            default => 0,
        };
    }

    /** Anyone holding a non-customer role counts against max_staff. */
    private static function staffCount(): int
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', '!=', 'user'))
            ->count();
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

    private static function storageGb(): float
    {
        return round(self::storageBytes() / 1073741824, 2);
    }
}
