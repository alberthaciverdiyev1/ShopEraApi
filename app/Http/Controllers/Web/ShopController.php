<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Concerns\InteractsWithModuleControllers;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(Request $request)
    {
        app()->setLocale('az');

        $categoryId = (int) $request->query('category') ?: null;
        $result = $this->searchProducts($request->query());

        return view('storefront.shop.index', [
            'items' => $result['items'],
            'meta' => $result['meta'],
            'categories' => $this->storefrontCategories(),
            'facets' => $this->storefrontFacets($categoryId),
            'dynamicFilters' => $this->storefrontCategoryFilters($categoryId),
            'categoryId' => $categoryId,
            'sorts' => $this->storefrontSorts(),
            'query' => $request->query(),
        ]);
    }
}
