<?php

namespace Modules\Filter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Filter\Http\Requests\FilterAddRequest;
use Modules\Filter\Http\Requests\FilterCategoryAssignRequest;
use Modules\Filter\Http\Requests\FilterUpdateRequest;
use Modules\Filter\Http\Requests\ProductFilterValuesRequest;
use Modules\Filter\Services\FilterService;

class FilterController extends Controller
{
    private FilterService $service;

    public function __construct(FilterService $service)
    {
        $this->middleware('auth:sanctum')->except(['list', 'categoryFilters', 'tree', 'productValues']);
        $this->middleware('permission:add filter')->only('add');
        $this->middleware('permission:update filter')->only(['update', 'setCategories']);
        $this->middleware('permission:delete filter')->only('delete');
        $this->middleware('permission:update product')->only('setProductValues');

        $this->service = $service;
    }

    /**
     * Every filter.
     */
    public function list()
    {
        return $this->service->list();
    }

    /**
     * A category's filters with their in-category values.
     */
    public function categoryFilters(Request $request)
    {
        $categoryId = (int) $request->query('category_id', 0);

        if ($categoryId <= 0) {
            return responseHelper(__('The category_id field is required.'), 422, []);
        }

        return $this->service->categoryFilters($categoryId);
    }

    /**
     * Dependent filter tree for a subcategory (brand → model → storage …).
     */
    public function tree(Request $request)
    {
        $categoryId = (int) $request->query('category_id', 0);

        if ($categoryId <= 0) {
            return responseHelper(__('The category_id field is required.'), 422, []);
        }

        return $this->service->tree($categoryId);
    }

    /**
     * One filter with its category ids.
     */
    public function details(int $id)
    {
        return $this->service->details($id);
    }

    /**
     * Add a filter.
     */
    public function add(FilterAddRequest $request)
    {
        return $this->service->add($request);
    }

    /**
     * Update a filter.
     */
    public function update(FilterUpdateRequest $request, int $id)
    {
        return $this->service->update($request, $id);
    }

    /**
     * Delete a filter.
     */
    public function delete(int $id)
    {
        return $this->service->delete($id);
    }

    /**
     * Replace the categories a filter is attached to.
     */
    public function setCategories(FilterCategoryAssignRequest $request, int $id)
    {
        return $this->service->setCategories($request, $id);
    }

    /**
     * A product's filter values.
     */
    public function productValues(Request $request)
    {
        $productId = (int) $request->query('product_id', 0);

        if ($productId <= 0) {
            return responseHelper(__('The product_id field is required.'), 422, []);
        }

        return $this->service->productValues($productId);
    }

    /**
     * Replace a product's filter values.
     */
    public function setProductValues(ProductFilterValuesRequest $request)
    {
        return $this->service->setProductValues($request);
    }
}
