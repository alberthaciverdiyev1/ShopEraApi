<?php

namespace Modules\Category\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Entities\Category;
use Modules\Product\Entities\Product;

class CategoryDatabaseSeeder extends Seeder
{
    private const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=640&q=80';

    /**
     * Category id by its full path ("Qadın geyimi/Üst geyim/Köynəklər").
     *
     * @var array<string, int>
     */
    private array $paths = [];

    public function run(): void
    {
        $catalog = $this->catalog();
        $sort = count($catalog);
        $keepIds = [];

        foreach ($catalog as $parent) {
            $parentCategory = Category::withTrashed()->updateOrCreate(
                ['name->az' => $parent['name'], 'parent_id' => null],
                [
                    'name' => $this->translations($parent['name']),
                    'image' => $parent['image'] ?? self::DEFAULT_IMAGE,
                    'is_active' => true,
                    'show_on_home' => true,
                    'sort_order' => $sort--,
                ]
            );

            if ($parentCategory->trashed()) {
                $parentCategory->restore();
            }

            $keepIds[] = $parentCategory->id;
            $this->paths[$parent['name']] = $parentCategory->id;
            $keepIds = array_merge(
                $keepIds,
                $this->syncChildren($parent['children'] ?? [], $parentCategory->id, $parent['name'])
            );
        }

        $this->reassignProducts();
        $this->pruneCategories($keepIds);
    }

    /**
     * @return array<int, int>
     */
    private function syncChildren(array $children, int $parentId, string $parentPath): array
    {
        $sort = count($children);
        $ids = [];

        foreach ($children as $child) {
            $category = Category::withTrashed()->updateOrCreate(
                ['name->az' => $child['name'], 'parent_id' => $parentId],
                [
                    'name' => $this->translations($child['name']),
                    'image' => $child['image'] ?? self::DEFAULT_IMAGE,
                    'is_active' => true,
                    'sort_order' => $sort--,
                ]
            );

            if ($category->trashed()) {
                $category->restore();
            }

            $ids[] = $category->id;
            $path = $parentPath.'/'.$child['name'];
            $this->paths[$path] = $category->id;

            if (! empty($child['children'])) {
                $ids = array_merge($ids, $this->syncChildren($child['children'], $category->id, $path));
            }
        }

        return $ids;
    }

    /**
     * Move products of the old fashion categories into their new homes before
     * the old tree is removed.
     */
    private function reassignProducts(): void
    {
        $legacy = [
            7 => 'Kişi geyimi',
            45 => 'Kişi geyimi/Üst geyim/Köynəklər',
            46 => 'Kişi geyimi/Alt geyim/Şalvarlar',
            47 => 'Kişi geyimi/Üst geyim/Gödəkçələr',
            347 => 'Kişi geyimi/Üst geyim/Futbolkalar',
            8 => 'Qadın geyimi',
            48 => 'Qadın geyimi/Alt geyim/Donlar',
            49 => 'Qadın geyimi/Üst geyim/Bluzlar',
            50 => 'Qadın geyimi/Alt geyim/Ətəklər',
            344 => 'Qadın geyimi/Alt geyim/Şalvarlar',
            345 => 'Qadın geyimi/Üst geyim/Sviterlər',
            346 => 'Qadın geyimi/Üst geyim',
            9 => 'Uşaq geyimi',
            10 => 'Ayaqqabı',
            348 => 'Aksesuarlar',
            349 => 'Aksesuarlar/Çantalar',
            350 => 'Aksesuarlar/Cüzdanlar',
            17 => 'Gözəllik/Makiyaj',
            18 => 'Gözəllik/Dəriyə qulluq',
            19 => 'Ətirlər',
            20 => 'Gözəllik/Saç qulluğu',
        ];

        foreach ($legacy as $oldId => $path) {
            $newId = $this->paths[$path] ?? null;

            if ($newId && $newId !== $oldId) {
                Product::withTrashed()
                    ->where('category_id', $oldId)
                    ->update(['category_id' => $newId]);
            }
        }
    }

    /**
     * Soft-delete every category that is not part of the fashion catalog.
     *
     * @param  array<int, int>  $keepIds
     */
    private function pruneCategories(array $keepIds): void
    {
        $keepIds = array_unique($keepIds);

        Category::query()
            ->whereNotIn('id', $keepIds)
            ->delete();

        // Products of the removed tree would otherwise keep showing up in the
        // storefront without a category; hide them instead of deleting data.
        Product::query()
            ->where(function ($query) use ($keepIds) {
                $query->whereNull('category_id')
                    ->orWhereNotIn('category_id', $keepIds);
            })
            ->update(['is_active' => false]);
    }

    private function translations(string $name): array
    {
        return ['az' => $name, 'en' => $name, 'ru' => $name, 'tr' => $name];
    }

    private function category(string $name, array $children = [], ?string $image = null): array
    {
        return array_filter([
            'name' => $name,
            'image' => $image,
            'children' => $children,
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function catalog(): array
    {
        return [
            $this->category('Qadın geyimi', [
                $this->category('Üst geyim', [
                    $this->category('Köynəklər'),
                    $this->category('Bluzlar'),
                    $this->category('Futbolkalar'),
                    $this->category('Sviterlər'),
                    $this->category('Gödəkçələr'),
                    $this->category('Palto və plaşlar'),
                ]),
                $this->category('Alt geyim', [
                    $this->category('Şalvarlar'),
                    $this->category('Cins şalvarlar'),
                    $this->category('Ətəklər'),
                    $this->category('Donlar'),
                    $this->category('Şortlar'),
                ]),
                $this->category('Paltarlar', [
                    $this->category('Gündəlik paltarlar'),
                    $this->category('Axşam paltarları'),
                ]),
                $this->category('Alt paltarı və corablar'),
            ], 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=640&q=80'),
            $this->category('Kişi geyimi', [
                $this->category('Üst geyim', [
                    $this->category('Köynəklər'),
                    $this->category('Futbolkalar'),
                    $this->category('Sviterlər'),
                    $this->category('Gödəkçələr'),
                    $this->category('Kostyumlar'),
                ]),
                $this->category('Alt geyim', [
                    $this->category('Şalvarlar'),
                    $this->category('Cins şalvarlar'),
                    $this->category('Şortlar'),
                ]),
                $this->category('Alt paltarı və corablar'),
            ], 'https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?auto=format&fit=crop&w=640&q=80'),
            $this->category('Uşaq geyimi', [
                $this->category('Oğlan geyimi'),
                $this->category('Qız geyimi'),
                $this->category('Körpə geyimi'),
                $this->category('Alt paltarı və corablar'),
            ], 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?auto=format&fit=crop&w=640&q=80'),
            $this->category('Ayaqqabı', [
                $this->category('Qadın ayaqqabıları'),
                $this->category('Kişi ayaqqabıları'),
                $this->category('Uşaq ayaqqabıları'),
                $this->category('İdman ayaqqabıları'),
                $this->category('Çəkmələr və botlar'),
                $this->category('Sandaletlər'),
            ], 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=640&q=80'),
            $this->category('Aksesuarlar', [
                $this->category('Çantalar'),
                $this->category('Cüzdanlar'),
                $this->category('Kəmərlər'),
                $this->category('Şərflər və eşarplar'),
                $this->category('Papaqlar'),
                $this->category('Eynəklər'),
                $this->category('Saatlar'),
                $this->category('Zərgərlik və bijuteriya'),
            ], 'https://images.unsplash.com/photo-1611085583191-a3b181a88401?auto=format&fit=crop&w=640&q=80'),
            $this->category('Ətirlər', [
                $this->category('Qadın ətirləri'),
                $this->category('Kişi ətirləri'),
                $this->category('Uniseks ətirlər'),
                $this->category('Ətir dəstləri'),
            ], 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=640&q=80'),
            $this->category('Gözəllik', [
                $this->category('Makiyaj'),
                $this->category('Dəriyə qulluq'),
                $this->category('Saç qulluğu'),
                $this->category('Dırnaq baxımı'),
            ], 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=640&q=80'),
            $this->category('İdman geyimi', [
                $this->category('Kişi idman geyimi'),
                $this->category('Qadın idman geyimi'),
                $this->category('Uşaq idman geyimi'),
                $this->category('İdman aksesuarları'),
            ], 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=640&q=80'),
        ];
    }
}
