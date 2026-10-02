<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Entities\Setting;

class SettingDatabaseSeeder extends Seeder
{
    /**
     * Store-wide configuration for ShopEra.az.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'instagram_url' => 'https://www.instagram.com/shopera.az',
                'facebook_url' => 'https://www.facebook.com/shopera.az',
                'twitter_url' => 'https://x.com/shopera_az',
                'youtube_url' => 'https://www.youtube.com/@shopera',
                'telegram_url' => 'https://t.me/shopera_az',
                'linkedin_url' => 'https://www.linkedin.com/company/shopera',
                'tiktok_url' => 'https://www.tiktok.com/@shopera.az',
                'whatsapp_number' => '994709990569',
                'phone_number_1' => '0709990569',
                'phone_number_2' => '0123100707',
                'phone_number_3' => '0509990569',
                'phone_number_4' => '0779990569',
                'google_map_url' => 'https://maps.app.goo.gl/2ixKnqaSq5AeoXsu8',
                'address' => 'Bakı, Nizami küçəsi 203',
                'referral_reward_amount' => 1.00,
                'app_version' => '1.2.0',
                'app_version_ios' => '1.2.0',
                'minimal_purchase_price' => 15.00,
                'wholesale_minimal_purchase_price' => 100.00,
                'store_commission_percent' => 10.00,
                'store_negative_balance_limit' => 10.00,
                'store_handover_hours' => 24,
                'store_late_penalty_amount' => 5.00,
                'public_low_stock_threshold' => 20,
                'seller_instructions' => [
                    'az' => 'Məhsulu 24 saat ərzində kuryerə təhvil verin. Qablaşdırmanın bütöv olduğuna əmin olun.',
                    'en' => 'Hand the product over to the courier within 24 hours and make sure the packaging is intact.',
                ],
            ]
        );
    }
}
