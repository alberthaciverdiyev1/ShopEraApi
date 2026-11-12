<?php

namespace App\Support\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Category\Http\Controllers\CategoryController;
use Modules\Filter\Http\Controllers\FilterController;
use Modules\Product\Http\Controllers\ProductController;

/**
 * Lets a web controller reuse the module controllers the mobile API calls.
 *
 * Web pages never query the database themselves: they build a request for the
 * module controller (with the session user attached) and translate its JSON
 * response into view data.
 */
trait InteractsWithModuleControllers
{
    private const SORTS = [
        'created_at:desc',
        'price:asc',
        'price:desc',
        'sales_count:desc',
    ];

    /**
     * Build and validate a module FormRequest for a direct controller call.
     */
    protected function moduleRequest(string $requestClass, array $data = [], string $verb = 'POST', array $files = []): FormRequest
    {
        /** @var FormRequest $request */
        $request = $requestClass::create('/', $verb, $data, [], $files);
        $request->setContainer(app());
        $request->setUserResolver(fn () => auth()->user());
        $request->validateResolved();

        return $request;
    }

    /**
     * Build a plain request for a module controller that takes Request.
     */
    protected function moduleHttpRequest(array $data = [], string $verb = 'GET', array $files = []): Request
    {
        $request = Request::create('/', $verb, $data, [], $files);
        $request->setUserResolver(fn () => auth()->user());

        return $request;
    }

    /**
     * The `data` payload of a module controller response.
     */
    protected function payload(mixed $response): mixed
    {
        if ($response instanceof JsonResponse) {
            return $response->getData(true)['data'] ?? [];
        }

        if ($response instanceof Response) {
            $decoded = json_decode($response->getContent(), true);

            return is_array($decoded) ? ($decoded['data'] ?? []) : [];
        }

        return $response;
    }

    /**
     * Root categories with their descendants, as the storefront menus expect.
     */
    protected function storefrontCategories(): array
    {
        $flat = (array) $this->payload(
            app(CategoryController::class)->list($this->moduleHttpRequest(['all' => 1]))
        );

        $byParent = [];

        foreach ($flat as $category) {
            $byParent[(int) ($category['parent_id'] ?? 0)][] = $category;
        }

        $build = function (int $parentId) use (&$build, $byParent): array {
            return array_map(function (array $category) use (&$build): array {
                $category['children'] = $build((int) $category['id']);

                return $category;
            }, $byParent[$parentId] ?? []);
        };

        return $build(0);
    }

    /**
     * @return array{items: array, meta: array}
     */
    protected function searchProducts(array $query): array
    {
        $response = app(ProductController::class)->list(
            $this->moduleHttpRequest($this->normaliseCatalogQuery($query))
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

    protected function storefrontFacets(?int $categoryId): array
    {
        $payload = app(ProductController::class)
            ->filters($this->moduleHttpRequest([
                'category_ids' => $categoryId ? [$categoryId] : [],
            ]))
            ->getData(true);

        return $payload['data'] ?? [
            'brands' => [],
            'colors' => [],
            'sizes' => [],
            'price' => ['min' => 0, 'max' => 0],
            'total' => 0,
        ];
    }

    protected function storefrontCategoryFilters(?int $categoryId): array
    {
        if (! $categoryId) {
            return [];
        }

        $payload = $this->payload(
            app(FilterController::class)->categoryFilters(
                $this->moduleHttpRequest(['category_id' => $categoryId])
            )
        );

        return is_array($payload) ? $payload : [];
    }

    protected function storefrontSorts(): array
    {
        return self::SORTS;
    }

    /**
     * Translate clean query-string input into the params ProductService expects.
     */
    protected function normaliseCatalogQuery(array $query): array
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
