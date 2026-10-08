<?php

namespace Modules\Marketplace\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Entities\Category;
use Modules\Delivery\Entities\City;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

/**
 * Demo data for the marketplace, scoped to the Electronics branch only:
 * marks its leaf categories as brand-based and creates a handful of
 * listings (phones, laptops, tablets, TVs) with real brands/models.
 */
class ElectronicsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $electronics = Category::query()->where('name->az', 'Elektronika')->first();

        if (! $electronics) {
            $this->command?->warn('“Elektronika” kateqoriyası tapılmadı.');

            return;
        }

        // Leaf categories under Electronics ask for a brand + model.
        $leaves = Category::query()->where('parent_id', $electronics->id)->get();
        foreach ($leaves as $leaf) {
            $leaf->forceFill(['needs_brand' => true])->save();
        }

        $byName = fn (string $name) => $leaves->firstWhere('name.az', $name) ?? $leaves->first();
        $phone = $byName('Telefonlar');
        $laptop = $byName('Noutbuklar');
        $tablet = $byName('Planşetlər');
        $tv = $byName('Televizorlar');

        $brand = fn (string $name) => \Illuminate\Support\Facades\DB::table('brands')->where('name', $name)->value('id');

        $cityId = City::query()->where('key', 'Baku')->value('id');
        $userId = User::query()->orderBy('id')->value('id');

        $locales = ['az', 'en', 'ru', 'tr'];
        $tr = fn (string $text) => array_fill_keys($locales, $text);

        $items = [
            ['DEMO-ELEK-01', 'iPhone 15 Pro Max 256GB', 'Apple', 'iPhone 15 Pro Max', $phone, 3199, 'new'],
            ['DEMO-ELEK-02', 'Samsung Galaxy S24 Ultra 512GB', 'Samsung', 'Galaxy S24 Ultra', $phone, 2799, 'new'],
            ['DEMO-ELEK-03', 'Xiaomi 14 12/256GB', 'Xiaomi', 'Xiaomi 14', $phone, 1499, 'new'],
            ['DEMO-ELEK-04', 'Huawei P60 Pro', 'Huawei', 'P60 Pro', $phone, 1699, 'used'],
            ['DEMO-ELEK-05', 'MacBook Air M2 13" 256GB', 'Apple', 'MacBook Air M2', $laptop, 2499, 'new'],
            ['DEMO-ELEK-06', 'Asus ROG Strix G16 RTX 4060', 'Asus', 'ROG Strix G16', $laptop, 3299, 'new'],
            ['DEMO-ELEK-07', 'Lenovo IdeaPad 3 15"', 'Lenovo', 'IdeaPad 3', $laptop, 1099, 'used'],
            ['DEMO-ELEK-08', 'iPad Air 5 64GB Wi-Fi', 'Apple', 'iPad Air 5', $tablet, 1399, 'new'],
            ['DEMO-ELEK-09', 'Samsung Galaxy Tab S9 128GB', 'Samsung', 'Galaxy Tab S9', $tablet, 1299, 'used'],
            ['DEMO-ELEK-10', 'LG OLED C3 55" 4K', 'LG', 'OLED C3 55', $tv, 2199, 'new'],
            ['DEMO-ELEK-11', 'Sony Bravia XR A80L 65"', 'Sony', 'Bravia XR A80L', $tv, 2999, 'used'],
        ];

        foreach ($items as [$sku, $title, $brandName, $model, $category, $price, $condition]) {
            if (! $category) {
                continue;
            }

            Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'title' => $tr($title),
                    'description' => $tr($title.' — demo elan. Zəng edin, razılaşaq.'),
                    'category_id' => $category->id,
                    'brand_id' => $brand($brandName),
                    'model' => $model,
                    'price' => $price,
                    'city_id' => $cityId,
                    'condition' => $condition,
                    'stock_count' => 1,
                    'user_id' => $userId,
                    'seller_type' => 'user',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]
            );
        }

        $this->command?->info('Elektronika demo datası hazırdır ('.count($items).' elan).');
    }
}
