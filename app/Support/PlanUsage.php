<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Modules\Category\Entities\Category;
use Modules\Order\Entities\Order;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

/**
 * Current consumption for the plan limits mirrored from Manager.ShopEra.
 * Keys match Features::LIMIT_KEYS so usage and limit line up one-to-one.
 */
class PlanUsage
{
    /** @return array<string,int|float> */
    public static function all(): array
    {
        return [
            'max_products' => Product::query()->count(),
            'max_categories' => Category::query()->count(),
            'max_staff' => self::staffCount(),
            // max_orders is a monthly cap, so usage counts the current month.
            'max_orders' => Order::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            'storage_gb' => self::storageGb(),
        ];
    }

    /** Anyone holding a non-customer role counts against max_staff. */
    private static function staffCount(): int
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', '!=', 'user'))
            ->count();
    }

    private static function storageGb(): float
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

        return round($bytes / 1073741824, 2);
    }
}
