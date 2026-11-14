<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Filter\Http\Controllers\FilterController;
use Modules\Product\Http\Controllers\ProductController;

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

        $facets = $this->storefrontFacets($categoryId);

        // A store chosen together with a category may have no products in it,
        // so it would be missing from the category-scoped list. Its entry is
        // added back so the sidebar keeps a checkbox the visitor can clear.
        $pickedVendors = array_map('intval', (array) ($resolved['vendors'] ?? []));
        if ($pickedVendors !== []) {
            $knownVendors = array_map(
                fn ($vendor) => (int) ($vendor['id'] ?? 0),
                (array) ($facets['vendors'] ?? [])
            );
            $allVendors = collect((array) ($this->storefrontFacets(null)['vendors'] ?? []))->keyBy('id');

            foreach ($pickedVendors as $vendorId) {
                if (! in_array($vendorId, $knownVendors, true) && $allVendors->has($vendorId)) {
                    $facets['vendors'][] = $allVendors->get($vendorId);
                }
            }
        }

        return view('storefront.shop.index', [
            'items' => $result['items'],
            'meta' => $result['meta'],
            'categories' => $this->storefrontCategories(),
            'facets' => $facets,
            'dynamicFilters' => $this->storefrontCategoryFilters($categoryId),
            'categoryId' => $categoryId,
            'sorts' => $this->storefrontSorts(),
            // The view builds filter links from the slugs the visitor used.
            'query' => $request->query(),
        ]);
    }

    /**
     * One page of a filter facet, used by the "show more" buttons so long
     * option lists are fetched on demand instead of rendered up front.
     */
    public function filterValues(Request $request)
    {
        app()->setLocale('az');

        $facet = (string) $request->query('facet', '');
        $resolved = $this->resolveCatalogSlugs(['category' => $request->query('category')]);
        $categoryId = (int) ($resolved['category'] ?? 0) ?: null;
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));

        if (in_array($facet, ['brands', 'colors', 'sizes', 'vendors'], true)) {
            $payload = app(ProductController::class)->facetValues($this->moduleHttpRequest([
                'type' => $facet,
                'category_ids' => $categoryId ? [$categoryId] : [],
                'page' => $page,
                'per_page' => $perPage,
            ]))->getData(true);

            return response()->json($payload);
        }

        if ($facet === 'filter') {
            $payload = app(FilterController::class)->values($this->moduleHttpRequest([
                'filter' => (string) $request->query('slug', ''),
                'category_id' => $categoryId ?? 0,
                'page' => $page,
                'per_page' => $perPage,
            ]))->getData(true);

            return response()->json($payload);
        }

        return response()->json([
            'success' => false,
            'status_code' => 422,
            'message' => 'Unknown filter facet.',
            'data' => [],
        ], 422);
    }
}
