<?php

namespace Modules\Filter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Entities\Category;
use Modules\Filter\Entities\Filter;
use Modules\Filter\Entities\ProductFilter;
use Modules\Product\Entities\Product;

class FilterDatabaseSeeder extends Seeder
{
    /**
     * Dynamic filters with sensible, category-aware values for every product
     * instead of random ones, so filtering returns coherent result sets.
     */
    public function run(): void
    {
        $definitions = [
            'material' => [
                'title' => ['az' => 'Material', 'en' => 'Material', 'ru' => 'Материал', 'tr' => 'Materyal'],
                'type' => 'select',
                'options' => ['Cotton', 'Wool', 'Polyester', 'Leather', 'Wood', 'Metal', 'Glass', 'Plastic', 'Steel'],
                'parents' => ['Fashion', 'Home & Living', 'Electronics'],
            ],
            'warranty' => [
                'title' => ['az' => 'Zəmanət', 'en' => 'Warranty', 'ru' => 'Гарантия', 'tr' => 'Garanti'],
                'type' => 'radio',
                'options' => ['6 months', '1 year', '2 years'],
                'parents' => ['Electronics', 'Home & Living', 'Sports'],
            ],
            'waterproof' => [
                'title' => ['az' => 'Suya davamlı', 'en' => 'Waterproof', 'ru' => 'Водонепроницаемый', 'tr' => 'Su geçirmez'],
                'type' => 'checkbox',
                'options' => ['Yes', 'No'],
                'parents' => ['Fashion', 'Sports'],
            ],
            'weight' => [
                'title' => ['az' => 'Çəki (kq)', 'en' => 'Weight (kg)', 'ru' => 'Вес (кг)', 'tr' => 'Ağırlık (kg)'],
                'type' => 'input',
                'options' => [],
                'parents' => ['Electronics', 'Sports', 'Home & Living'],
            ],
        ];

        $parents = Category::query()
            ->whereNull('parent_id')
            ->get()
            ->keyBy(fn (Category $c) => $c->getTranslation('name', 'en'));

        $parentNamesById = $parents->mapWithKeys(fn (Category $c) => [$c->id => $c->getTranslation('name', 'en')]);

        $childParents = Category::query()
            ->whereNotNull('parent_id')
            ->get()
            ->mapWithKeys(fn (Category $child) => [
                $child->id => $parentNamesById[$child->parent_id] ?? null,
            ]);

        $filters = [];

        foreach ($definitions as $key => $definition) {
            $filter = Filter::updateOrCreate(
                ['title->en' => $definition['title']['en']],
                [
                    'title' => $definition['title'],
                    'type' => $definition['type'],
                    'options' => $definition['options'],
                ]
            );

            $categoryIds = collect($definition['parents'])
                ->map(fn ($name) => $parents[$name]->id ?? null)
                ->filter()
                ->all();

            if ($categoryIds) {
                $filter->categories()->sync($categoryIds);
            }

            $filters[$key] = ['model' => $filter, 'definition' => $definition];
        }

        $materialMap = [
            'Men' => 'Cotton', 'Women' => 'Cotton', 'Kids' => 'Cotton',
            'Shoes' => 'Leather', 'Football' => 'Leather',
            'Furniture' => 'Wood', 'Kitchen' => 'Steel', 'Decor' => 'Glass', 'Lighting' => 'Plastic',
            'Phones' => 'Metal', 'Laptops' => 'Metal', 'Tablets' => 'Metal', 'TVs' => 'Metal',
        ];
        $warrantyMap = [
            'Phones' => '1 year', 'Laptops' => '1 year', 'Tablets' => '1 year', 'TVs' => '1 year',
            'Kitchen' => '1 year', 'Furniture' => '2 years', 'Fitness' => '2 years',
            'Cycling' => '2 years', 'Outdoor' => '2 years',
        ];
        $waterproofYes = ['Shoes', 'Football', 'Outdoor', 'Cycling'];

        Product::query()->with('category')->chunkById(100, function ($products) use ($filters, $childParents, $materialMap, $warrantyMap, $waterproofYes) {
            foreach ($products as $product) {
                $categoryEn = $product->category?->getTranslation('name', 'en');
                if (! $categoryEn) {
                    continue;
                }

                $parent = $childParents[$product->category_id] ?? $categoryEn;

                $values = [
                    'material' => $materialMap[$categoryEn] ?? 'Cotton',
                    'warranty' => $warrantyMap[$categoryEn] ?? null,
                    'waterproof' => in_array($categoryEn, $waterproofYes, true) ? 'Yes' : 'No',
                    'weight' => $product->weight !== null ? (string) $product->weight : (string) round($product->price / 20, 2),
                ];

                foreach ($values as $key => $value) {
                    if ($value === null) {
                        continue;
                    }
                    if (! in_array($parent, $filters[$key]['definition']['parents'], true)) {
                        continue;
                    }

                    ProductFilter::updateOrCreate(
                        ['product_id' => $product->id, 'filter_id' => $filters[$key]['model']->id],
                        ['value' => $value]
                    );
                }
            }
        });
    }
}
