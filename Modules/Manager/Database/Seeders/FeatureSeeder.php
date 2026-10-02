<?php

namespace Modules\Manager\Database\Seeders;

use Modules\Manager\Entities\Feature;
use Illuminate\Database\Seeder;

/**
 * Feature catalogue derived from what the ShopEra API + Svelte storefront
 * actually expose. Nothing speculative is listed here.
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        // key, name, type(bool|limit), default, group
        $features = [
            ['buy_with_card', 'Kartla ödəniş', 'bool', '1', 'Sifariş & Ödəniş'],
            ['buy_with_cash', 'Qapıda nağd ödəniş', 'bool', '1', 'Sifariş & Ödəniş'],
            ['buy_with_whatsapp', 'WhatsApp ilə sifariş', 'bool', '1', 'Sifariş & Ödəniş'],
            ['delivery_prices', 'Çatdırılma qiymətləri', 'bool', '1', 'Çatdırılma'],
            ['delivery_cities', 'Çatdırılma şəhərləri', 'bool', '1', 'Çatdırılma'],
            ['pickup_points', 'Gəl al nöqtələri', 'bool', '1', 'Çatdırılma'],
            ['delivery_info', 'Çatdırılma məlumatları', 'bool', '1', 'Çatdırılma'],
        ];

        foreach ($features as $i => [$key, $name, $type, $default, $group]) {
            Feature::query()->updateOrCreate(
                ['key' => $key],
                ['name' => $name, 'type' => $type, 'default_value' => $default, 'group' => $group, 'sort_order' => $i]
            );
        }
    }
}
