<?php

namespace Modules\Filter\Services;

use Illuminate\Http\JsonResponse;
use Modules\Category\Entities\Category;
use Modules\Filter\Entities\CategoryFilter;
use Modules\Filter\Entities\Filter;
use Modules\Filter\Entities\FilterValue;
use Modules\Filter\Entities\ProductFilter;
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
     *
     * Filters attached to the category (or one of its ancestors) are always
     * returned, even before any product carries a value for them. Filters that
     * only occur through product values keep working as before.
     */
    public function categoryFilters(int $categoryId): JsonResponse
    {
        if ($categoryId <= 0) {
            return $this->commonFilters();
        }

        $category = Category::query()->find($categoryId);

        $categoryIds = $category
            ? collect($category->ancestorChain())->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [$categoryId];

        $attachedIds = CategoryFilter::query()
            ->whereIn('category_id', $categoryIds)
            ->pluck('filter_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $filters = $this->model->query()
            ->where(function ($query) use ($categoryId, $attachedIds) {
                $query->whereHas('productValues.product', fn ($q) => $q->where('category_id', $categoryId));

                if ($attachedIds !== []) {
                    $query->orWhereIn('id', $attachedIds);
                }
            })
            ->orderBy('sort_order')
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
     * One page of the values products carry for a dynamic filter.
     */
    public function values($request): JsonResponse
    {
        $key = (string) $request->query('filter', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));
        $categoryId = (int) $request->query('category_id', 0);

        $filter = is_numeric($key)
            ? $this->model->newQuery()->find((int) $key)
            : $this->model->newQuery()->where('slug', $key)->first();

        if (! $filter) {
            return responseHelper(__('Filter not found.'), 404, []);
        }

        $base = ProductFilter::query()
            ->join('products', 'products.id', '=', 'product_filters.product_id')
            ->where('product_filters.filter_id', $filter->id)
            ->whereNull('products.deleted_at')
            ->whereNotNull('product_filters.value')
            ->where('product_filters.value', '<>', '');

        if ($categoryId > 0) {
            $base->where('products.category_id', $categoryId);
        }

        $total = (clone $base)->distinct()->count('product_filters.value');

        $values = (clone $base)
            ->distinct()
            ->orderBy('product_filters.value')
            ->forPage($page, $perPage)
            ->pluck('product_filters.value')
            ->values();

        return responseHelper(__('Filter values retrieved successfully.'), 200, [
            'filter' => [
                'id' => $filter->id,
                'slug' => $filter->slug,
                'title' => $filter->title,
            ],
            'items' => $values->map(fn ($value) => [
                'value' => (string) $value,
                'name' => (string) $value,
            ])->all(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => $page * $perPage < $total,
        ]);
    }

    /**
     * Filters shared by most of the catalog (material, season, fit, …), shown
     * on the shop page before a category is picked.
     */
    private function commonFilters(): JsonResponse
    {
        $commonIds = CategoryFilter::query()
            ->select('filter_id')
            ->groupBy('filter_id')
            ->havingRaw('count(*) >= 4')
            ->pluck('filter_id')
            ->all();

        $filters = $this->model->query()
            ->whereIn('id', $commonIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $filters->each(fn (Filter $filter) => $filter->values = []);

        return responseHelper(__('Filters retrieved successfully.'), 200, FilterResource::collection($filters));
    }

    /**
     * The dependent filter tree for a subcategory: each filter with all of its
     * values (flat, each carrying parent_value_id). The client filters a
     * child's options by the parent value it currently has selected.
     */
    public function tree(int $categoryId): JsonResponse
    {
        // Filters are attached to top-level categories through the pivot and
        // inherited by their descendants, so walk the ancestor chain too.
        $category = Category::query()->find($categoryId);

        $categoryIds = $category
            ? collect($category->ancestorChain())->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [$categoryId];

        $filters = $this->model->query()
            ->where(function ($query) use ($categoryId, $categoryIds) {
                $query->where('category_id', $categoryId)
                    ->orWhereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['values'])
            ->get();

        $data = $filters->map(fn (Filter $filter) => [
            'id' => $filter->id,
            'title' => $filter->title,
            'type' => $filter->type,
            'required' => (bool) $filter->required,
            'sort_order' => $filter->sort_order,
            'depends_on_filter_id' => $filter->depends_on_filter_id,
            // Flat option labels let the listing form offer a choice even
            // when no filter_values rows exist yet.
            'options' => array_values((array) ($filter->options ?? [])),
            'values' => $filter->values->map(fn (FilterValue $value) => [
                'id' => $value->id,
                'title' => $value->title,
                'parent_value_id' => $value->parent_value_id,
            ])->all(),
        ])->all();

        return responseHelper(__('Filters retrieved successfully.'), 200, $data);
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
            ->with('filter')
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

    /** Filters attached to a category (empty when no category chosen). */
    public function forCategory(?int $categoryId)
    {
        if (! $categoryId) {
            return collect();
        }

        return $this->model->newQuery()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
            ->orderBy('id')
            ->get();
    }

    /** A product's saved filter values: [filter_id => value]. */
    public function productValuesFor(?int $productId): array
    {
        if (! $productId) {
            return [];
        }

        return ProductFilter::query()
            ->where('product_id', $productId)
            ->pluck('value', 'filter_id')
            ->all();
    }

    /** Category select options: [id => label]. */
    public function categoryOptions(): array
    {
        $options = [];

        foreach (Category::query()->orderBy('id')->get() as $category) {
            $options[$category->id] = admin_label($category, 'name', '#'.$category->id);
        }

        return $options;
    }
}
