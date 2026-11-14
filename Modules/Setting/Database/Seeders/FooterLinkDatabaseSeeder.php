<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Entities\FooterLink;
use Modules\Setting\Services\FooterLinkService;

class FooterLinkDatabaseSeeder extends Seeder
{
    /**
     * The columns the storefront ships with. The admin panel can edit,
     * reorder, disable or replace any of them afterwards.
     */
    public function run(): void
    {
        $links = [
            ['catalog', 10, '/shop', 'Bütün elanlar', 'All listings', 'Все объявления', 'Tüm ilanlar'],
            ['catalog', 20, '/categories', 'Kateqoriyalar', 'Categories', 'Категории', 'Kategoriler'],
            ['catalog', 30, '/blog', 'Bloq', 'Blog', 'Блог', 'Blog'],

            ['information', 10, '/about', 'Haqqımızda', 'About us', 'О нас', 'Hakkımızda'],
            ['information', 20, '/privacy', 'Məxfilik siyasəti', 'Privacy policy', 'Политика конфиденциальности', 'Gizlilik politikası'],
            ['information', 30, '/terms', 'İstifadə şərtləri', 'Terms of use', 'Условия использования', 'Kullanım şartları'],
            ['information', 40, '/faq', 'Tez-tez verilən suallar', 'Frequently asked questions', 'Часто задаваемые вопросы', 'Sıkça sorulan sorular'],

            ['contact', 10, 'tel:+994500000000', '+994 50 000 00 00', '+994 50 000 00 00', '+994 50 000 00 00', '+994 50 000 00 00'],
            ['contact', 20, 'mailto:info@shopera.az', 'info@shopera.az', 'info@shopera.az', 'info@shopera.az', 'info@shopera.az'],
            ['contact', 30, '/contact', 'Əlaqə forması', 'Contact form', 'Форма обратной связи', 'İletişim formu'],
        ];

        foreach ($links as [$section, $order, $url, $az, $en, $ru, $tr]) {
            FooterLink::updateOrCreate(
                ['section' => $section, 'url' => $url],
                [
                    'label' => ['az' => $az, 'en' => $en, 'ru' => $ru, 'tr' => $tr],
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }

        FooterLinkService::forgetCache();
    }
}
