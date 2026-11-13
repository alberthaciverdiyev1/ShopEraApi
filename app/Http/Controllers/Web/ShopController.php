<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(Request $request)
    {
        app()->setLocale('az');

        // Filters arrive as slugs; the module still works with ids.
        $resolved = $this->resolveCatalogSlugs($request->query());
        $categoryId = (int) ($resolved['category'] ?? 0) ?: null;
        $result = $this->searchProducts($resolved);

        return view('storefront.shop.index', [
            'items' => $result['items'],
            'meta' => $result['meta'],
            'categories' => $this->storefrontCategories(),
            'facets' => $this->storefrontFacets($categoryId),
            'dynamicFilters' => $this->storefrontCategoryFilters($categoryId),
            'categoryId' => $categoryId,
            'sorts' => $this->storefrontSorts(),
            // The view builds filter links from the slugs the visitor used.
            'query' => $request->query(),
        ]);
    }
}
