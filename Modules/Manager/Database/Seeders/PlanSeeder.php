<?php

namespace Modules\Manager\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Manager\Entities\Feature;
use Modules\Manager\Entities\Plan;

/**
 * Free / Premium / Business matrix.
 *
 * Tiered (limit) features use: 0 = off/none, 1 = basic, 2 = full, 3 = advanced,
 * -1 = unlimited. Boolean features are on/off.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $featureIds = Feature::query()->pluck('id', 'key');

        $plans = [
            'free' => [
                'name' => 'Free', 'price' => 0, 'sort_order' => 0,
                'on' => ['products', 'categories', 'subdomain', 'theme_selection', 'whatsapp_orders'],
                'off' => [
                    'site_orders', 'online_payment', 'custom_domain', 'dark_light_theme',
                    'promo_codes', 'shipping_settings', 'google_analytics', 'custom_code',
                    'multi_language',
                ],
                'values' => [
                    'product_variants' => '1', 'stock_tracking' => '1', 'branding' => '0', 'seo' => '1',
                    'color_customization' => '0', 'font_customization' => '0', 'component_selection' => '0',
                    'homepage_editing' => '0', 'sales_reports' => '0', 'support_level' => '0',
                ],
                'limits' => ['max_products' => '25', 'max_staff' => '0'],
            ],

            'premium' => [
                'name' => 'Premium', 'price' => 29, 'sort_order' => 1,
                'on' => [
                    'products', 'categories', 'subdomain', 'theme_selection', 'dark_light_theme',
                    'online_payment', 'custom_domain', 'promo_codes', 'shipping_settings',
                    'google_analytics', 'site_orders', 'whatsapp_orders', 'multi_language',
                ],
                'off' => ['custom_code'],
                'values' => [
                    'product_variants' => '2', 'stock_tracking' => '2', 'branding' => '1', 'seo' => '2',
                    'color_customization' => '1', 'font_customization' => '1', 'component_selection' => '1',
                    'homepage_editing' => '1', 'sales_reports' => '1', 'support_level' => '1',
                ],
                'limits' => ['max_products' => '500', 'max_staff' => '2'],
            ],

            'business' => [
                'name' => 'Business', 'price' => 99, 'sort_order' => 2,
                'on' => [
                    'products', 'categories', 'subdomain', 'theme_selection', 'dark_light_theme',
                    'online_payment', 'custom_domain', 'promo_codes', 'shipping_settings',
                    'google_analytics', 'site_orders', 'whatsapp_orders', 'multi_language',
                    'custom_code', 
                ],
                'off' => [],
                'values' => [
                    'product_variants' => '2', 'stock_tracking' => '2', 'branding' => '2', 'seo' => '3',
                    'color_customization' => '2', 'font_customization' => '2', 'component_selection' => '2',
                    'homepage_editing' => '2', 'sales_reports' => '2', 'support_level' => '2',
                ],
                'limits' => ['max_products' => '-1', 'max_staff' => '-1'],
            ],
        ];

        // Every feature not covered by the matrix stays enabled on all plans.
        $managed = collect($plans)->flatMap(fn ($p) => array_merge(
            $p['on'], $p['off'], array_keys($p['values']), array_keys($p['limits'])
        ))->unique();
        $rest = $featureIds->keys()->reject(fn ($key) => $managed->contains($key));

        foreach ($plans as $slug => $data) {
            $plan = Plan::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'billing_cycle' => 'monthly',
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );

            $sync = [];
            foreach ($rest as $key) {
                $sync[$featureIds[$key]] = ['value' => '1'];
            }
            foreach ($data['on'] as $key) {
                if (isset($featureIds[$key])) {
                    $sync[$featureIds[$key]] = ['value' => '1'];
                }
            }
            foreach ($data['off'] as $key) {
                if (isset($featureIds[$key])) {
                    $sync[$featureIds[$key]] = ['value' => '0'];
                }
            }
            foreach ($data['values'] as $key => $value) {
                if (isset($featureIds[$key])) {
                    $sync[$featureIds[$key]] = ['value' => (string) $value];
                }
            }
            foreach ($data['limits'] as $key => $value) {
                if (isset($featureIds[$key])) {
                    $sync[$featureIds[$key]] = ['value' => (string) $value];
                }
            }

            $plan->features()->sync($sync);
        }

        // Drop plans that are no longer part of the product (e.g. basic/pro).
        Plan::query()->whereNotIn('slug', ['free', 'premium', 'business'])->get()->each(function (Plan $plan) {
            if ($plan->subscriptions()->exists()) {
                $plan->update(['is_active' => false]);
            } else {
                $plan->delete();
            }
        });
    }
}
