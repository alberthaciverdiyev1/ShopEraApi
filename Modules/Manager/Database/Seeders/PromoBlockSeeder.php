<?php

namespace Modules\Manager\Database\Seeders;

use Modules\Manager\Entities\PromoBlock;
use Illuminate\Database\Seeder;

class PromoBlockSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = [
            [
                'type' => 'offer', 'badge' => '-30%', 'sort_order' => 1,
                'title' => 'Limited time offer', 'subtitle' => 'Get Bundle products offer',
                'description' => 'Bundle məhsulları seç və birlikdə al — 30%-ə qədər endirim.',
                'button_text' => 'İndi al', 'url' => '/shop',
            ],
            [
                'type' => 'ad', 'sort_order' => 2,
                'title' => 'Burada reklam ola bilər', 'subtitle' => 'Reklam yeri',
                'description' => 'Banner/əlaqə məlumatı', 'button_text' => 'Ətraflı', 'url' => '/contact',
            ],
        ];

        foreach ($blocks as $block) {
            PromoBlock::query()->updateOrCreate(
                ['type' => $block['type'], 'title' => $block['title']],
                $block + ['is_active' => true]
            );
        }
    }
}
