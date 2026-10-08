<?php

namespace Modules\Marketplace\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Category\Entities\Category;
use Modules\Delivery\Entities\City;
use Modules\Filter\Entities\Filter;
use Modules\Filter\Entities\FilterValue;
use Modules\Filter\Entities\ProductFilterValue;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

/**
 * Demo data for the marketplace, scoped to the Electronics branch. Phones get
 * a dependent filter tree (Brand -> Model -> Storage / RAM); the listings are
 * created and linked to filter values.
 */
class ElectronicsDemoSeeder extends Seeder
{
    private array $locales = ['az', 'en', 'ru', 'tr'];

    public function run(): void
    {
        $electronics = Category::query()->where('name->az', 'Elektronika')->first();
        if (! $electronics) {
            $this->command?->warn('“Elektronika” kateqoriyası tapılmadı.');

            return;
        }

        $leaves = Category::query()->where('parent_id', $electronics->id)->get();
        // Brand/model/storage are filters now, not a category flag.
        foreach ($leaves as $leaf) {
            $leaf->forceFill(['needs_brand' => false])->save();
        }

        $leafByAz = fn (string $az) => $leaves->first(
            fn (Category $category) => ($category->getTranslations('name')['az'] ?? null) === $az
        );

        $phone = $leafByAz('Telefonlar');
        $laptop = $leafByAz('Noutbuklar');
        $tablet = $leafByAz('Planşetlər');
        $tv = $leafByAz('Televizorlar');

        $cityId = City::query()->where('key', 'Baku')->value('id');
        $userId = User::query()->orderBy('id')->value('id');

        // ---- Listings -----------------------------------------------------
        $items = [
            ['DEMO-ELEK-01', 'iPhone 15 Pro Max 256GB', $phone, 3199, 'new'],
            ['DEMO-ELEK-02', 'Samsung Galaxy S24 Ultra 512GB', $phone, 2799, 'new'],
            ['DEMO-ELEK-03', 'Xiaomi 14 12/256GB', $phone, 1499, 'new'],
            ['DEMO-ELEK-04', 'Huawei P60 Pro', $phone, 1699, 'used'],
            ['DEMO-ELEK-05', 'MacBook Air M2 13" 256GB', $laptop, 2499, 'new'],
            ['DEMO-ELEK-06', 'Asus ROG Strix G16 RTX 4060', $laptop, 3299, 'new'],
            ['DEMO-ELEK-07', 'Lenovo IdeaPad 3 15"', $laptop, 1099, 'used'],
            ['DEMO-ELEK-08', 'iPad Air 5 64GB Wi-Fi', $tablet, 1399, 'new'],
            ['DEMO-ELEK-09', 'Samsung Galaxy Tab S9 128GB', $tablet, 1299, 'used'],
            ['DEMO-ELEK-10', 'LG OLED C3 55" 4K', $tv, 2199, 'new'],
            ['DEMO-ELEK-11', 'Sony Bravia XR A80L 65"', $tv, 2999, 'used'],
        ];

        $products = [];
        foreach ($items as [$sku, $title, $category, $price, $condition]) {
            if (! $category) {
                continue;
            }
            $products[$sku] = Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'title' => $this->tr($title),
                    'description' => $this->tr($title.' — demo elan. Zəng edin, razılaşaq.'),
                    'category_id' => $category->id,
                    'brand_id' => null,
                    'model' => null,
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

        if (! $phone) {
            $this->command?->info('Elektronika demo datası hazırdır (filtr yoxdur).');

            return;
        }

        // ---- Phone filter tree: Brand -> Model -> Storage / RAM -----------
        $brand = $this->filter($phone->id, 'Marka', 1, null, true);
        $model = $this->filter($phone->id, 'Model', 2, $brand->id, true);
        $storage = $this->filter($phone->id, 'Yaddaş', 3, $model->id, false);
        $ram = $this->filter($phone->id, 'RAM', 4, $model->id, false);

        // brand => models => [storage options]
        $catalogue = [
            'Apple' => ['iPhone 15 Pro Max' => ['256GB', '512GB'], 'iPhone 15' => ['128GB', '256GB']],
            'Samsung' => ['Galaxy S24 Ultra' => ['256GB', '512GB', '1TB'], 'Galaxy S24' => ['128GB', '256GB']],
            'Xiaomi' => ['Xiaomi 14' => ['256GB', '512GB']],
            'Huawei' => ['P60 Pro' => ['256GB']],
        ];

        $linked = [
            'DEMO-ELEK-01' => ['Apple', 'iPhone 15 Pro Max', '256GB', '8GB'],
            'DEMO-ELEK-02' => ['Samsung', 'Galaxy S24 Ultra', '512GB', '12GB'],
            'DEMO-ELEK-03' => ['Xiaomi', 'Xiaomi 14', '256GB', '12GB'],
            'DEMO-ELEK-04' => ['Huawei', 'P60 Pro', '256GB', '8GB'],
        ];

        foreach ($catalogue as $brandName => $models) {
            $brandValue = $this->value($brand->id, null, $brandName);

            foreach ($models as $modelName => $storages) {
                $modelValue = $this->value($model->id, $brandValue->id, $modelName);

                foreach ($storages as $size) {
                    $this->value($storage->id, $modelValue->id, $size);
                }

                foreach (['8GB', '12GB'] as $size) {
                    $this->value($ram->id, $modelValue->id, $size);
                }
            }
        }

        // Link demo phone listings to their filter values.
        foreach ($linked as $sku => [$brandName, $modelName, $storageName, $ramName]) {
            $product = $products[$sku] ?? null;
            if (! $product) {
                continue;
            }

            $brandValue = $this->value($brand->id, null, $brandName);
            $modelValue = $this->value($model->id, $brandValue->id, $modelName);
            $storageValue = $this->value($storage->id, $modelValue->id, $storageName);
            $ramValue = $this->value($ram->id, $modelValue->id, $ramName);

            ProductFilterValue::query()->where('product_id', $product->id)->delete();
            foreach ([$brand->id => $brandValue, $model->id => $modelValue, $storage->id => $storageValue, $ram->id => $ramValue] as $filterId => $value) {
                ProductFilterValue::query()->create([
                    'product_id' => $product->id,
                    'filter_id' => $filterId,
                    'filter_value_id' => $value->id,
                ]);
            }
        }

        $this->command?->info('Elektronika demo datası hazırdır ('.count($items).' elan, telefon filtrləri ilə).');
    }

    private function filter(int $categoryId, string $title, int $sort, ?int $dependsOn, bool $required): Filter
    {
        return Filter::query()->updateOrCreate(
            ['category_id' => $categoryId, 'title->az' => $title],
            [
                'title' => $this->tr($title),
                'type' => 'select',
                'sort_order' => $sort,
                'depends_on_filter_id' => $dependsOn,
                'required' => $required,
            ]
        );
    }

    private function value(int $filterId, ?int $parentValueId, string $title): FilterValue
    {
        return FilterValue::query()->updateOrCreate(
            ['filter_id' => $filterId, 'parent_value_id' => $parentValueId, 'title->az' => $title],
            ['title' => $this->tr($title)]
        );
    }

    private function tr(string $text): array
    {
        return array_fill_keys($this->locales, $text);
    }
}
