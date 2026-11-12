<?php

namespace App\Services\Storefront;

use Modules\Product\Services\ProductService;

/**
 * Product details page data. ProductService::details() already builds the
 * exact payload mobile and the Svelte storefront consume, so this service
 * only resolves the listing and derives presentational values from it.
 */
class ProductDetailService
{
    private const CORE_KEYS = ['brend', 'brand', 'kateqoriya', 'category', 'üslub', 'uslub', 'style', 'məqsəd', 'meqsed', 'purpose'];

    public function __construct(
        private readonly ProductService $products,
        private readonly CatalogService $catalog,
    ) {}

    public function byId(int $id): ?array
    {
        $payload = $this->products->details($id)->getData(true);

        if (empty($payload['success']) || empty($payload['data'])) {
            return null;
        }

        return $this->present($payload['data']);
    }

    public function bySlug(string $slug): ?array
    {
        $payload = $this->products->detailsBySlug($slug)->getData(true);

        if (empty($payload['success']) || empty($payload['data'])) {
            return null;
        }

        return $this->present($payload['data']);
    }

    /**
     * Add the values the Blade view renders: gallery, money, specs and the
     * category/brand characteristics block.
     */
    private function present(array $product): array
    {
        $images = collect($product['images'] ?? [])
            ->pluck('image_path')
            ->filter()
            ->values()
            ->all();

        if ($images === []) {
            $images = ['/assets/images/top-deals-item/topDealsItemThumb1_1.png'];
        }

        $price = (float) ($product['price'] ?? 0);
        $salePrice = (float) ($product['discount'] ?? 0);
        $hasDiscount = $salePrice > 0 && $salePrice < $price;
        $currentPrice = $hasDiscount ? $salePrice : $price;
        $discountPercent = ($hasDiscount && $price > 0)
            ? (int) round((($price - $salePrice) / $price) * 100)
            : 0;

        $specs = $this->specs($product);
        $characteristics = $this->characteristics($product);

        return $product + [
            'gallery' => $images,
            'current_price' => $currentPrice,
            'original_price' => $price,
            'has_discount' => $hasDiscount,
            'discount_percent' => $discountPercent,
            'product_code' => ($product['sku'] ?? null) ?: str_pad((string) $product['id'], 6, '0', STR_PAD_LEFT),
            'specs' => $specs,
            'characteristics' => $characteristics,
            'details' => array_merge($specs, $characteristics),
            'reviews' => $product['reviews'] ?? [],
            'review_count' => count($product['reviews'] ?? []) ?: (int) ($product['rate_count'] ?? 0),
            'category_path' => $product['category_path'] ?? [],
        ];
    }

    /**
     * Listing filters chosen when the product was posted (brand/model/…).
     */
    private function specs(array $product): array
    {
        $listingFilters = collect($product['filter_values'] ?? [])
            ->filter(fn ($value) => ! empty($value['filter']) && ! empty($value['value']))
            ->map(fn ($value) => ['label' => (string) $value['filter'], 'value' => (string) $value['value']])
            ->values()
            ->all();

        if ($listingFilters !== []) {
            return $listingFilters;
        }

        $filters = collect($product['filters'] ?? [])
            ->filter(fn ($filter) => ! in_array(mb_strtolower(trim((string) ($filter['name'] ?? ''))), self::CORE_KEYS, true))
            ->map(fn ($filter) => [
                'label' => (string) ($filter['name'] ?? $filter['title'] ?? 'Parametr'),
                'value' => (string) ($filter['value'] ?? ''),
            ])
            ->filter(fn ($filter) => $filter['value'] !== '')
            ->values()
            ->all();

        return $filters;
    }

    /**
     * Brand, category trail, city and style — the always-on characteristics.
     */
    private function characteristics(array $product): array
    {
        $list = [];

        if (! empty($product['brand']['name'])) {
            $list[] = [
                'label' => 'Brend',
                'value' => $product['brand']['name'],
                'link' => ! empty($product['brand']['id']) ? '/shop?brand='.$product['brand']['id'] : null,
            ];
        }

        $path = collect($product['category_path'] ?? [])->filter(fn ($category) => ! empty($category['name']))->values();

        if ($path->count() >= 2) {
            $parent = $path[$path->count() - 2];
            $leaf = $path[$path->count() - 1];

            $list[] = [
                'label' => 'Kateqoriya',
                'value' => $parent['name'],
                'link' => '/shop?category='.$parent['id'],
            ];
            $list[] = [
                'label' => 'Alt kateqoriya',
                'value' => $leaf['name'],
                'link' => '/shop?category='.$leaf['id'],
            ];
        } elseif ($path->count() === 1) {
            $list[] = [
                'label' => 'Kateqoriya',
                'value' => $path[0]['name'],
                'link' => '/shop?category='.$path[0]['id'],
            ];
        } elseif (! empty($product['category']['name'])) {
            $list[] = [
                'label' => 'Kateqoriya',
                'value' => $product['category']['name'],
                'link' => ! empty($product['category']['id']) ? '/shop?category='.$product['category']['id'] : null,
            ];
        }

        if (! empty($product['city'])) {
            $list[] = ['label' => 'Şəhər', 'value' => $product['city'], 'link' => null];
        }

        if (! empty($product['gender'])) {
            $list[] = ['label' => 'Üslub', 'value' => $product['gender'], 'link' => null];
        }

        return $list;
    }

    /**
     * Up to five other listings from the same category.
     */
    public function similar(array $product, int $limit = 5): array
    {
        $categoryId = $product['category']['id'] ?? null;

        if (! $categoryId) {
            return [];
        }

        $items = $this->catalog->search(['category' => $categoryId])['items'];

        return collect($items)
            ->reject(fn ($item) => (int) $item['id'] === (int) $product['id'])
            ->take($limit)
            ->values()
            ->all();
    }
}
