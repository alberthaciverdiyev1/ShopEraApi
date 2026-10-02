<?php

namespace Modules\PromoCode\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PromoCode\Entities\PromoCode;

class PromoCodeDatabaseSeeder extends Seeder
{
    /**
     * Active marketing promo codes.
     */
    public function run(): void
    {
        $codes = [
            ['WELCOME10', 10, 1000, true],
            ['SNAKER15', 15, 500, true],
            ['SUMMER20', 20, 300, true],
            ['BLACKFRIDAY30', 30, 200, true],
            ['RAMADAN25', 25, 400, true],
            ['NEWYEAR2026', 15, 600, true],
            ['VIPCLUB40', 40, 50, true],
            ['LOYAL25', 25, 150, true],
            ['REFERRAL10', 10, 0, true],
            ['CLEARANCE35', 35, 120, false],
        ];

        foreach ($codes as [$code, $percent, $userCount, $active]) {
            PromoCode::updateOrCreate(
                ['code' => $code],
                [
                    'discount_percent' => $percent,
                    'user_count' => $userCount,
                    'is_active' => $active,
                ]
            );
        }
    }
}
