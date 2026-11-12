<?php

namespace Modules\Setting\Services;

use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * A cheap "data revision" token for the clients' in-memory cache. It changes
 * whenever any cacheable catalogue/content row is updated, so a client can
 * drop its cache exactly when the admin changes something.
 */
class DataVersionService
{
    private const TABLES = [
        'products', 'categories', 'brands', 'colors', 'sizes', 'filters',
        'banners', 'popups', 'settings', 'promo_codes', 'stories', 'blogs',
    ];

    public function version(): string
    {
        $max = '';

        foreach (self::TABLES as $table) {
            try {
                $value = (string) DB::table($table)->max('updated_at');
                if ($value > $max) {
                    $max = $value;
                }
            } catch (\Throwable) {
                // Table may not exist on an older tenant — ignore.
            }
        }

        return md5($max.'|'.(TenantContext::database() ?? ''));
    }
}
