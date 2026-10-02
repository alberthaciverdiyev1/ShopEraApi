<?php

namespace App\Http\Controllers\Api;

use App\Support\TenantContext;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * A cheap "data revision" token for the storefront's in-memory cache. It
 * changes whenever any cacheable catalogue/content row is updated, so the
 * Svelte client can drop its cache exactly when the admin changes something.
 */
class DataVersionController extends Controller
{
    private const TABLES = [
        'products', 'categories', 'brands', 'colors', 'sizes', 'filters',
        'banners', 'popups', 'settings', 'promo_codes', 'stories', 'blogs',
    ];

    public function index()
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

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'OK',
            'data' => ['version' => md5($max.'|'.(TenantContext::database() ?? ''))],
        ]);
    }
}
