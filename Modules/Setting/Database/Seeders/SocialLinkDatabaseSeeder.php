<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Entities\SocialLink;
use Modules\Setting\Services\SocialLinkService;

class SocialLinkDatabaseSeeder extends Seeder
{
    /**
     * The networks the footer ships with, each with a placeholder address.
     * Existing rows are left alone so a deploy never overwrites the URLs the
     * admin panel already set; only missing networks are filled in.
     */
    public function run(): void
    {
        $links = [
            ['facebook', 10, 'https://facebook.com/shopera'],
            ['instagram', 20, 'https://instagram.com/shopera'],
            ['telegram', 30, 'https://t.me/shopera'],
            ['whatsapp', 40, 'https://wa.me/994500000000'],
            ['youtube', 50, 'https://youtube.com/@shopera'],
            ['tiktok', 60, 'https://tiktok.com/@shopera'],
            ['x', 70, 'https://x.com/shopera'],
            ['linkedin', 80, 'https://linkedin.com/company/shopera'],
        ];

        foreach ($links as [$platform, $order, $url]) {
            SocialLink::firstOrCreate(
                ['platform' => $platform],
                ['url' => $url, 'sort_order' => $order, 'is_active' => true]
            );
        }

        SocialLinkService::forgetCache();
    }
}
