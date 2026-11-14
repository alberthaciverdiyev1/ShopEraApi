<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Entities\SocialLink;
use Modules\Setting\Services\SocialLinkService;

class SocialLinkDatabaseSeeder extends Seeder
{
    /**
     * The icons the footer ships with. The admin panel can edit the URLs,
     * reorder, disable or add more networks afterwards.
     */
    public function run(): void
    {
        $links = [
            ['facebook', 10, 'https://facebook.com/'],
            ['instagram', 20, 'https://instagram.com/'],
            ['telegram', 30, 'https://t.me/'],
        ];

        foreach ($links as [$platform, $order, $url]) {
            SocialLink::updateOrCreate(
                ['platform' => $platform],
                ['url' => $url, 'sort_order' => $order, 'is_active' => true]
            );
        }

        SocialLinkService::forgetCache();
    }
}
