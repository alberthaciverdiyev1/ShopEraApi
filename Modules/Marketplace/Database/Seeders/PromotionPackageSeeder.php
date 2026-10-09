<?php

namespace Modules\Marketplace\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Marketplace\Entities\PromotionPackage;

/**
 * Default placement packages for the three tiers (İrəli çək / VIP / Premium).
 * Admins can adjust names, prices and copy from the promotions panel — this
 * seeder is idempotent and only fills the shipped defaults.
 */
class PromotionPackageSeeder extends Seeder
{
    public function run(): void
    {
        $locales = ['az', 'en', 'ru', 'tr'];
        $t = fn (string $value) => array_fill_keys($locales, $value);

        $promotedDescription = 'Elan bütün və axtarış nəticələrinin içində birinci yerə qalxacaq.';
        $vipDescription = 'Elan axtarış nəticələrində irəli çəkiləcək və öz kateqoriyasındakı bütün VIP elanlar arasında xidmətin aktivlik müddətinin sonunadək təsadüfi qaydada göstəriləcək.';
        $premiumDescription = 'Elan axtarış nəticələrində irəli çəkiləcək, öz kateqoriyasındakı bütün VIP elanlar arasında təsadüfi qaydada göstəriləcək və xidmətin aktivlik müddətinin sonunadək əsas səhifədə qalacaq.';
        $bonus = '14 Oktyabr 2026, 17:09 tarixinə kimi ödənilib';

        $packages = [
            [PromotionPackage::TYPE_PROMOTED, 1, '3 dəfə (8 saatdan bir)', 1, 1.00, $promotedDescription, null],
            [PromotionPackage::TYPE_PROMOTED, 2, '9 dəfə (8 saatdan bir)', 3, 1.80, $promotedDescription, null],
            [PromotionPackage::TYPE_PROMOTED, 3, '15 dəfə (8 saatdan bir)', 5, 2.90, $promotedDescription, null],
            [PromotionPackage::TYPE_PROMOTED, 4, '30 dəfə (8 saatdan bir)', 10, 4.50, $promotedDescription, null],

            [PromotionPackage::TYPE_VIP, 1, '5 gün', 5, 7.00, $vipDescription, $bonus],
            [PromotionPackage::TYPE_VIP, 2, '15 gün', 15, 16.00, $vipDescription, $bonus],
            [PromotionPackage::TYPE_VIP, 3, '30 gün', 30, 25.00, $vipDescription, $bonus],

            [PromotionPackage::TYPE_PREMIUM, 1, '1 gün', 1, 8.00, $premiumDescription, $bonus],
            [PromotionPackage::TYPE_PREMIUM, 2, '5 gün', 5, 17.00, $premiumDescription, $bonus],
            [PromotionPackage::TYPE_PREMIUM, 3, '15 gün', 15, 35.00, $premiumDescription, $bonus],
            [PromotionPackage::TYPE_PREMIUM, 4, '30 gün', 30, 45.00, $premiumDescription, $bonus],
        ];

        foreach ($packages as [$type, $sort, $name, $days, $price, $description, $bonusText]) {
            PromotionPackage::query()->updateOrCreate(
                ['type' => $type, 'sort_order' => $sort],
                [
                    'name' => $t($name),
                    'description' => $t($description),
                    'bonus' => $bonusText !== null ? $t($bonusText) : null,
                    'days' => $days,
                    'price' => $price,
                    'is_active' => true,
                ],
            );
        }
    }
}
