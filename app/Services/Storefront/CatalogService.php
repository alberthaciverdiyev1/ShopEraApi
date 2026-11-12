<?php

namespace App\Services\Storefront;

use Illuminate\Http\Request;
use Modules\Category\Entities\Category;
use Modules\Category\Http\Transformers\CategoryResource;
use Modules\Filter\Services\FilterService;
use Modules\Product\Services\ProductService;

/**
 * Storefront catalogue reads. The API endpoints stay the single source of
 * truth for filtering and ordering, so Blade pages reuse the module services
 * instead of duplicating any query logic.
 */
class CatalogService
{
    private const SORTS = [
        'created_at:desc',
        'price:asc',
        'price:desc',
        'sales_count:desc',
    ];

    public function __construct(
        private readonly ProductService $products,
        private readonly FilterService $filters,
    ) {}

    /**
     * Page of products for GET /shop.
     *
     * @return array{items: array, meta: array}
     */
    public function search(array $query): array
    {
        $response = $this->products->list(
            Request::create('/api/product', 'GET', $this->normalise($query))
        );

        $payload = $response->getData(true);

        return [
            'items' => $payload['data'] ?? [],
            'meta' => $payload['meta'] ?? [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 24,
                'total' => 0,
            ],
        ];
    }

    /**
     * Sidebar facets (brands, colours, sizes, price range) for a category.
     */
    public function facets(?int $categoryId): array
    {
        $payload = $this->products->filters(
            Request::create('/api/product/filters', 'GET', [
                'category_ids' => $categoryId ? [$categoryId] : [],
            ])
        )->getData(true);

        return $payload['data'] ?? [
            'brands' => [],
            'colors' => [],
            'sizes' => [],
            'price' => ['min' => 0, 'max' => 0],
            'total' => 0,
        ];
    }

    /**
     * Filters defined for a category, with the values used by its products.
     */
    public function categoryFilters(?int $categoryId): array
    {
        if (! $categoryId) {
            return [];
        }

        $payload = $this->filters->categoryFilters($categoryId)->getData(true);

        return is_array($payload['data'] ?? null) ? $payload['data'] : [];
    }

    /**
     * Root categories with one level of children for the sidebar tree.
     */
    public function categories(): array
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->withCount('products')
            ->with(['children' => fn ($query) => $query->withCount('products')->orderByDesc('sort_order')])
            ->orderByDesc('sort_order')
            ->get();

        return CategoryResource::collection($categories)->resolve(request());
    }

    public function sortOptions(): array
    {
        return self::SORTS;
    }

    /**
     * Translate clean query-string input into the params ProductService expects.
     */
    private function normalise(array $query): array
    {
        $params = [
            'marketplace' => true,
            'per_page' => 24,
        ];

        if (! empty($query['search'])) {
            $params['search'] = trim((string) $query['search']);
        }

        foreach (['category' => 'category_ids', 'brand' => 'brand_ids', 'color' => 'color_ids', 'size' => 'size_ids'] as $input => $param) {
            $value = (int) ($query[$input] ?? 0);

            if ($value > 0) {
                $params[$param] = [$value];
            }
        }

        if (isset($query['price_max']) && is_numeric($query['price_max']) && (float) $query['price_max'] > 0) {
            $params['price_max'] = (float) $query['price_max'];
        }

        if (! empty($query['vendor_id'])) {
            $params['vendor_id'] = (int) $query['vendor_id'];
        }

        if (! empty($query['sort'])) {
            [$orderBy, $orderType] = array_pad(explode(':', (string) $query['sort'], 2), 2, null);

            if (in_array($orderBy, ['created_at', 'price', 'sales_count'], true)) {
                $params['order_by'] = $orderBy;
                $params['order_type'] = $orderType === 'asc' ? 'asc' : 'desc';
            }
        }

        if (! empty($query['filters']) && is_array($query['filters'])) {
            $filters = [];

            foreach ($query['filters'] as $filterId => $values) {
                $values = array_values(array_filter((array) $values, fn ($value) => is_scalar($value) && (string) $value !== ''));

                if ($values !== []) {
                    $filters[(int) $filterId] = $values;
                }
            }

            if ($filters !== []) {
                $params['filters'] = $filters;
            }
        }

        if (! empty($query['page']) && (int) $query['page'] > 1) {
            $params['page'] = (int) $query['page'];
        }

        return $params;
    }
}
