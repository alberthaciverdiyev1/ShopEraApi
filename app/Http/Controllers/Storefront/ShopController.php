<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\CatalogService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(Request $request)
    {
        app()->setLocale('az');

        $categoryId = (int) $request->query('category') ?: null;
        $result = $this->catalog->search($request->query());

        return view('storefront.shop.index', [
            'items' => $result['items'],
            'meta' => $result['meta'],
            'categories' => $this->catalog->categories(),
            'facets' => $this->catalog->facets($categoryId),
            'dynamicFilters' => $this->catalog->categoryFilters($categoryId),
            'categoryId' => $categoryId,
            'sorts' => $this->catalog->sortOptions(),
            'query' => $request->query(),
        ]);
    }
}
