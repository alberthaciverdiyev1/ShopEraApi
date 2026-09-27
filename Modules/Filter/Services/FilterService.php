<?php

namespace Modules\Filter\Services;

use Illuminate\Http\JsonResponse;
use Modules\Filter\Http\Entities\CategoryFilter;
use Modules\Filter\Http\Entities\Filter;
use Modules\Filter\Http\Entities\ProductFilter;
use Modules\Filter\Http\Resources\FilterResource;
use Modules\Filter\Http\Resources\ProductFilterResource;

class FilterService
{
    private Filter $model;

    public function __construct(Filter $model)
    {
        $this->model = $model;
    }

    /**
     * Every filter, ordered by id.
     */
    public function list(): JsonResponse
    {
        $filters = $this->model->query()->orderBy('id')->get();

        return responseHelper(__('Filters retrieved successfully.'), 200, FilterResource::collection($filters));
    }

    /**
     * A category's filters together with the values found among its products.
     */
    public function categoryFilters(int $categoryId): JsonResponse
    {
        $filters = $this->model->query()
            ->whereHas('productValues.product', function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('id')
            ->get();

        $values = ProductFilter::query()
            ->select('filter_id', 'value')
            ->join('products', 'products.id', '=', 'product_filters.product_id')
            ->where('products.category_id', $categoryId)
            ->whereNull('products.deleted_at')
            ->whereNotNull('product_filters.value')
            ->where('product_filters.value', '<>', '')
            ->distinct()
            ->get()
            ->groupBy('filter_id')
            ->map(fn ($rows) => $rows->pluck('value')->values()->all());

        $filters->each(function (Filter $filter) use ($values) {
            $filter->values = $values->get($filter->id, []);
        });

        return responseHelper(__('Filters retrieved successfully.'), 200, FilterResource::collection($filters));
    }

    /**
     * One filter with the category ids it is attached to.
     */
    public function details(int $id): JsonResponse
    {
        $filter = $this->model->find($id);

        if (! $filter) {
            return responseHelper(__('Filter not found.'), 404, []);
        }

        $filter->values = [];
        $filter->category_ids = CategoryFilter::query()
            ->where('filter_id', $id)
            ->pluck('category_id')
            ->all();

        return responseHelper(__('Filter retrieved successfully.'), 200, array_merge(
            (new FilterResource($filter))->toArray(request()),
            ['category_ids' => $filter->category_ids]
        ));
    }

    /**
     * Add a filter.
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        $filter = handleTransaction(
            fn () => $this->model->create([
                'title' => $validated['title'],
                'type' => $validated['type'],
                'options' => $validated['options'] ?? [],
            ])->refresh(),
            'Filter added successfully.',
            FilterResource::class
        );

        return $filter;
    }

    /**
     * Update a filter.
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        return handleTransaction(
            function () use ($validated, $id) {
                $filter = $this->model->findOrFail($id);
                $filter->update([
                    'title' => $validated['title'],
                    'type' => $validated['type'],
                    'options' => $validated['options'] ?? [],
                ]);

                return $filter->refresh();
            },
            'Filter updated successfully.',
            FilterResource::class
        );
    }

    /**
     * Delete a filter (category/product links cascade).
     */
    public function delete(int $id): JsonResponse
    {
        return handleTransaction(
            function () use ($id) {
                $filter = $this->model->findOrFail($id);
                $filter->categories()->detach();
                $filter->productValues()->delete();
                $filter->delete();

                return $filter;
            },
            'Filter deleted successfully.'
        );
    }

    /**
     * Replace the categories a filter is attached to.
     */
    public function setCategories($request, int $id): JsonResponse
    {
        $categoryIds = $request->validated()['category_ids'] ?? [];

        return handleTransaction(
            function () use ($id, $categoryIds) {
                $filter = $this->model->findOrFail($id);
                $filter->categories()->sync($categoryIds);

                return $filter;
            },
            'Filter categories updated successfully.'
        );
    }

    /**
     * A product's filter values.
     */
    public function productValues(int $productId): JsonResponse
    {
        $values = ProductFilter::query()
            ->where('product_id', $productId)
            ->orderBy('filter_id')
            ->get();

        return responseHelper(__('Product filters retrieved successfully.'), 200, ProductFilterResource::collection($values));
    }

    /**
     * Replace a product's filter values.
     */
    public function setProductValues($request): JsonResponse
    {
        $validated = $request->validated();
        $productId = $validated['product_id'];
        $values = $validated['values'] ?? [];

        return handleTransaction(
            function () use ($productId, $values) {
                ProductFilter::query()->where('product_id', $productId)->delete();

                foreach ($values as $item) {
                    ProductFilter::query()->create([
                        'product_id' => $productId,
                        'filter_id' => $item['filter_id'],
                        'value' => $item['value'] ?? null,
                    ]);
                }

                return null;
            },
            'Product filters updated successfully.'
        );
    }
}
