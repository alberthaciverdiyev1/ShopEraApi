<?php

namespace Modules\Manager\Database\Seeders;

use Modules\Manager\Entities\Feature;
use Modules\Manager\Entities\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $featureIds = Feature::query()->pluck('id', 'key');

        $boolKeys = [
            'products', 'categories', 'brands', 'colors', 'sizes', 'product_filters', 'product_images',
            'product_videos', 'product_story_videos', 'bulk_price_update', 'ai_description', 'stock_subscriptions',
            'banners', 'popups', 'stories', 'blog', 'faq', 'legal_terms', 'contact_page', 'sitemap_seo', 'theme_colors', 'custom_theme',
            'orders', 'order_receipt', 'online_payment', 'cash_on_delivery', 'saved_cards', 'balance_wallet', 'refunds',
            'whatsapp_orders', 'phone_orders', 'instagram_orders',
            'delivery_prices', 'delivery_cities', 'delivery_info', 'pickup_points', 'fast_delivery',
            'promo_codes', 'promo_blocks', 'referrals', 'reviews', 'chat', 'auto_reply', 'push_notifications', 'sms',
            'marketplace', 'store_commission', 'store_settings',
            'users', 'roles_permissions', 'addresses', 'favorites', 'basket',
            'multi_language', 'statistics', 'settings', 'custom_domain',
        ];

        $basicOff = [
            'product_filters', 'product_videos', 'product_story_videos', 'bulk_price_update', 'ai_description',
            'stock_subscriptions', 'blog', 'theme_colors', 'saved_cards', 'balance_wallet', 'refunds',
            'pickup_points', 'fast_delivery', 'referrals', 'chat', 'auto_reply', 'push_notifications', 'promo_blocks',
            'marketplace', 'store_commission', 'store_settings', 'multi_language', 'statistics', 'custom_domain',
            'custom_theme',
        ];

        $proOff = ['ai_description', 'marketplace', 'store_commission', 'store_settings', 'promo_blocks'];

        // Free plan: only the essentials + WhatsApp ordering, so small sellers
        // without a card account can start taking orders right away.
        $freeAllowed = [
            'orders', 'whatsapp_orders', 'products', 'categories', 'users', 'settings', 'promo_blocks',
            'contact_page', 'sitemap_seo', 'sms', 'basket', 'addresses', 'product_images',
            'faq', 'legal_terms', 'cash_on_delivery', 'delivery_cities', 'delivery_info',
        ];
        $freeOff = array_values(array_diff($boolKeys, $freeAllowed));

        $plans = [
            'free' => [
                'name' => 'Free', 'price' => 0, 'billing_cycle' => 'monthly', 'sort_order' => 0,
                'off' => $freeOff,
                'limits' => ['max_products' => '20', 'max_categories' => '5', 'max_staff' => '1', 'max_orders' => '100', 'storage_gb' => '1'],
            ],
            'basic' => [
                'name' => 'Basic', 'price' => 29, 'billing_cycle' => 'monthly', 'sort_order' => 1,
                'off' => $basicOff,
                'limits' => ['max_products' => '100', 'max_categories' => '20', 'max_staff' => '2', 'max_orders' => '500', 'storage_gb' => '5'],
            ],
            'pro' => [
                'name' => 'Pro', 'price' => 79, 'billing_cycle' => 'monthly', 'sort_order' => 2,
                'off' => $proOff,
                'limits' => ['max_products' => '2000', 'max_categories' => '200', 'max_staff' => '10', 'max_orders' => '5000', 'storage_gb' => '50'],
            ],
            'business' => [
                'name' => 'Business', 'price' => 199, 'billing_cycle' => 'monthly', 'sort_order' => 3,
                'off' => ['promo_blocks'],
                'limits' => ['max_products' => '-1', 'max_categories' => '-1', 'max_staff' => '-1', 'max_orders' => '-1', 'storage_gb' => '500'],
            ],
        ];

        foreach ($plans as $slug => $data) {
            $plan = Plan::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $data['name'], 'price' => $data['price'], 'billing_cycle' => $data['billing_cycle'], 'sort_order' => $data['sort_order'], 'is_active' => true]
            );

            $sync = [];
            foreach ($boolKeys as $key) {
                if (isset($featureIds[$key])) {
                    $sync[$featureIds[$key]] = ['value' => in_array($key, $data['off'], true) ? '0' : '1'];
                }
            }
            foreach ($data['limits'] as $key => $value) {
                if (isset($featureIds[$key])) {
                    $sync[$featureIds[$key]] = ['value' => $value];
                }
            }

            $plan->features()->sync($sync);
        }
    }
}
