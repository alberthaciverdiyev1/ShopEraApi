<?php

namespace Modules\Filter\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Modules\Category\Entities\Category;
use Modules\Filter\Entities\Filter;
use Modules\Filter\Entities\ProductFilter;
use Modules\Product\Entities\Product;

class FilterDatabaseSeeder extends Seeder
{
    /**
     * Fashion filters modelled on the ones Trendyol offers for clothing,
     * shoes, bags and perfume. Each filter is attached to the categories it
     * belongs to; the storefront shows it for those categories and their
     * descendants.
     */
    public function run(): void
    {
        $categories = Category::withTrashed()->get();
        $pathsById = $this->pathsById($categories);
        $idByPath = array_flip($pathsById);

        // Demo values from the old catalog no longer match the new options.
        ProductFilter::query()->delete();

        $filters = [];

        foreach ($this->definitions() as $key => $definition) {
            $filter = Filter::updateOrCreate(
                ['title->az' => $definition['title']['az']],
                [
                    'title' => $definition['title'],
                    'type' => $definition['type'],
                    'options' => $definition['options'],
                    'sort_order' => $definition['sort_order'],
                ]
            );

            $ids = collect($definition['attach'])
                ->map(fn (string $path) => $idByPath[$path] ?? null)
                ->filter()
                ->values()
                ->all();

            $filter->categories()->sync($ids);
            $filters[$key] = $filter;
        }

        $this->deleteUnusedFilters($filters);
        $this->seedProductValues($filters, $pathsById);
    }

    /**
     * @param  array<string, Filter>  $filters
     */
    private function deleteUnusedFilters(array $filters): void
    {
        Filter::query()
            ->whereNotIn('id', collect($filters)->pluck('id')->all())
            ->get()
            ->each(function (Filter $filter) {
                $filter->categories()->detach();
                $filter->productValues()->delete();
                $filter->delete();
            });
    }

    /**
     * Give existing demo products plausible values so the new filters return
     * results instead of empty lists.
     *
     * @param  array<string, Filter>  $filters
     * @param  array<int, string>  $pathsById
     */
    private function seedProductValues(array $filters, array $pathsById): void
    {
        $fashionRoots = ['Qadın geyimi', 'Kişi geyimi', 'Uşaq geyimi', 'Ayaqqabı', 'Aksesuarlar', 'Ətirlər', 'Gözəllik', 'İdman geyimi'];

        Product::query()->chunkById(100, function (Collection $products) use ($filters, $pathsById, $fashionRoots) {
            foreach ($products as $product) {
                $path = $pathsById[$product->category_id] ?? null;

                if (! $path) {
                    continue;
                }

                $root = explode('/', $path)[0];

                if (! in_array($root, $fashionRoots, true)) {
                    continue;
                }

                foreach ($this->valuesFor($path, $root) as $key => $value) {
                    if (! isset($filters[$key]) || $value === null) {
                        continue;
                    }

                    ProductFilter::updateOrCreate(
                        ['product_id' => $product->id, 'filter_id' => $filters[$key]->id],
                        ['value' => $value]
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string|null>
     */
    private function valuesFor(string $path, string $root): array
    {
        $isClothing = in_array($root, ['Qadın geyimi', 'Kişi geyimi', 'Uşaq geyimi', 'İdman geyimi'], true);
        $isUpper = str_contains($path, 'Üst geyim');
        $isBag = str_contains($path, 'Aksesuarlar/Çantalar') || str_contains($path, 'Aksesuarlar/Cüzdanlar');
        $isPerfume = $root === 'Ətirlər';

        return [
            'material' => match (true) {
                $isBag => 'Dəri',
                $root === 'Ayaqqabı' => 'Dəri',
                $root === 'İdman geyimi' => 'Polyester',
                $isPerfume => null,
                default => 'Pambıq',
            },
            'season' => $isPerfume ? null : 'Bütün mövsümlər',
            'fit' => $isClothing ? 'Regular' : null,
            'collar' => $isUpper ? 'Dairəvi' : null,
            'sleeve' => $isUpper ? 'Qısa qol' : null,
            'heel' => $root === 'Ayaqqabı' ? 'Düz' : null,
            'closure' => $root === 'Ayaqqabı' ? 'Bağcıqlı' : null,
            'bag_type' => $isBag ? 'Əl çantası' : null,
            'gender' => $isPerfume ? 'Uniseks' : null,
            'perfume_volume' => $isPerfume ? '100 ml' : null,
            'perfume_family' => $isPerfume ? 'Fresh' : null,
            'kid_age' => $root === 'Uşaq geyimi' || str_contains($path, 'Uşaq ayaqqabıları') ? '3-6 yaş' : null,
            'skin_type' => $root === 'Gözəllik'
                ? (str_contains($path, 'Dəriyə qulluq') ? 'Qarışıq' : 'Normal')
                : null,
            'hair_type' => str_contains($path, 'Saç qulluğu') ? 'Düz' : null,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function pathsById(Collection $categories): array
    {
        $byId = $categories->keyBy('id');
        $paths = [];

        foreach ($byId as $id => $category) {
            $parts = [];
            $node = $category;
            $guard = 0;

            while ($node && $guard++ < 10) {
                array_unshift($parts, (string) $node->getTranslation('name', 'az'));
                $node = $node->parent_id ? ($byId[$node->parent_id] ?? null) : null;
            }

            $paths[(int) $id] = implode('/', $parts);
        }

        return $paths;
    }

    /**
     * @return array<string, array{title: array<string, string>, type: string, options: array<int, string>, attach: array<int, string>, sort_order: int}>
     */
    private function definitions(): array
    {
        $clothing = ['Qadın geyimi', 'Kişi geyimi', 'Uşaq geyimi', 'İdman geyimi'];
        $allFashion = array_merge($clothing, ['Ayaqqabı', 'Aksesuarlar']);

        return [
            'material' => [
                'title' => $this->title('Material', 'Material', 'Материал', 'Materyal'),
                'type' => 'select',
                'options' => ['Pambıq', 'Yun', 'Polyester', 'İpək', 'Kətan', 'Dəri', 'Süni dəri', 'Denim', 'Trikotaj', 'Şifon', 'Digər'],
                'attach' => $allFashion,
                'sort_order' => 100,
            ],
            'season' => [
                'title' => $this->title('Mövsüm', 'Season', 'Сезон', 'Sezon'),
                'type' => 'checkbox',
                'options' => ['Yaz', 'Yay', 'Payız', 'Qış', 'Bütün mövsümlər'],
                'attach' => $allFashion,
                'sort_order' => 90,
            ],
            'fit' => [
                'title' => $this->title('Kəsim', 'Fit', 'Крой', 'Kalıp'),
                'type' => 'select',
                'options' => ['Regular', 'Slim fit', 'Oversize', 'Relaxed', 'Skinny'],
                'attach' => $clothing,
                'sort_order' => 80,
            ],
            'collar' => [
                'title' => $this->title('Yaxa növü', 'Collar', 'Воротник', 'Yaka'),
                'type' => 'select',
                'options' => ['Dairəvi', 'V-yaxa', 'Polo', 'Kapüşonlu', 'Köynək yaxası'],
                'attach' => ['Qadın geyimi', 'Kişi geyimi'],
                'sort_order' => 75,
            ],
            'sleeve' => [
                'title' => $this->title('Qol uzunluğu', 'Sleeve length', 'Длина рукава', 'Kol uzunluğu'),
                'type' => 'select',
                'options' => ['Qısa qol', 'Uzun qol', 'Qolsuz', '3/4 qol'],
                'attach' => ['Qadın geyimi', 'Kişi geyimi', 'İdman geyimi'],
                'sort_order' => 70,
            ],
            'heel' => [
                'title' => $this->title('Topuk hündürlüyü', 'Heel height', 'Высота каблука', 'Topuk yüksekliği'),
                'type' => 'select',
                'options' => ['Düz', 'Alçaq', 'Orta', 'Yüksək', 'Platforma'],
                'attach' => ['Ayaqqabı'],
                'sort_order' => 65,
            ],
            'closure' => [
                'title' => $this->title('Bağlanma növü', 'Closure', 'Застёжка', 'Bağlantı'),
                'type' => 'select',
                'options' => ['Bağcıqlı', 'Fermuar', 'Velcro', 'Kremp'],
                'attach' => ['Ayaqqabı'],
                'sort_order' => 60,
            ],
            'bag_type' => [
                'title' => $this->title('Çanta növü', 'Bag type', 'Тип сумки', 'Çanta tipi'),
                'type' => 'select',
                'options' => ['Əl çantası', 'Çiyin çantası', 'Sırt çantası', 'Klassik çanta', 'Çarpaz çanta', 'Klatç'],
                'attach' => ['Aksesuarlar/Çantalar'],
                'sort_order' => 55,
            ],
            'watch_type' => [
                'title' => $this->title('Saat növü', 'Watch type', 'Тип часов', 'Saat tipi'),
                'type' => 'select',
                'options' => ['Klassik', 'İdman', 'Ağıllı saat', 'Retro'],
                'attach' => ['Aksesuarlar/Saatlar'],
                'sort_order' => 50,
            ],
            'glasses_type' => [
                'title' => $this->title('Eynək növü', 'Glasses type', 'Тип очков', 'Gözlük tipi'),
                'type' => 'select',
                'options' => ['Günəş eynəyi', 'Optik eynək', 'İdman eynəyi'],
                'attach' => ['Aksesuarlar/Eynəklər'],
                'sort_order' => 45,
            ],
            'jewelry_material' => [
                'title' => $this->title('Zərgərlik materialı', 'Jewellery material', 'Материал украшения', 'Takı materyali'),
                'type' => 'select',
                'options' => ['Qızıl', 'Gümüş', 'Polad', 'Bürünc', 'Mis'],
                'attach' => ['Aksesuarlar/Zərgərlik və bijuteriya'],
                'sort_order' => 40,
            ],
            'gender' => [
                'title' => $this->title('Cins', 'Gender', 'Пол', 'Cinsiyet'),
                'type' => 'radio',
                'options' => ['Qadın', 'Kişi', 'Uniseks', 'Uşaq'],
                'attach' => ['Ətirlər'],
                'sort_order' => 35,
            ],
            'perfume_volume' => [
                'title' => $this->title('Ətir həcmi', 'Volume', 'Объём', 'Hacim'),
                'type' => 'select',
                'options' => ['30 ml', '50 ml', '100 ml', '150 ml', '200 ml'],
                'attach' => ['Ətirlər'],
                'sort_order' => 30,
            ],
            'perfume_family' => [
                'title' => $this->title('Ətir qrupu', 'Fragrance family', 'Группа аромата', 'Koku grubu'),
                'type' => 'select',
                'options' => ['Çiçəkli', 'Şərq', 'Taxta', 'Sian', 'Şirin', 'Fresh'],
                'attach' => ['Ətirlər'],
                'sort_order' => 25,
            ],
            'kid_age' => [
                'title' => $this->title('Uşaq yaş qrupu', 'Kids age', 'Возраст ребёнка', 'Çocuk yaş aralığı'),
                'type' => 'select',
                'options' => ['0-2 yaş', '3-6 yaş', '7-12 yaş', '13+ yaş'],
                'attach' => ['Uşaq geyimi', 'Ayaqqabı/Uşaq ayaqqabıları'],
                'sort_order' => 20,
            ],
            'skin_type' => [
                'title' => $this->title('Dəri tipi', 'Skin type', 'Тип кожи', 'Cilt tipi'),
                'type' => 'select',
                'options' => ['Quru', 'Yağlı', 'Qarışıq', 'Həssas', 'Normal'],
                'attach' => ['Gözəllik'],
                'sort_order' => 15,
            ],
            'hair_type' => [
                'title' => $this->title('Saç tipi', 'Hair type', 'Тип волос', 'Saç tipi'),
                'type' => 'select',
                'options' => ['Düz', 'Dalğalı', 'Qıvrım', 'Boyalı', 'Zədələnmiş'],
                'attach' => ['Gözəllik/Saç qulluğu'],
                'sort_order' => 10,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function title(string $az, string $en, string $ru, string $tr): array
    {
        return ['az' => $az, 'en' => $en, 'ru' => $ru, 'tr' => $tr];
    }
}
