<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Entities\Product;
use Modules\Product\Http\Resources\ProductResource;

/**
 * The home screen in one call: Snaker's own catalogue.
 *
 * This is a new endpoint rather than a change to /api/product or
 * /api/category/with-products. Those are what the builds already on the App
 * Store and Google Play call on every launch, and reshaping their responses
 * would break the app in customers' hands. Nothing here touches them.
 */
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $productLimit = min(max((int) $request->input('product_limit', 20), 1), 50);

        return responseHelper('Home retrieved successfully.', 200, [
            'house_products' => $this->houseProducts($request, $productLimit),
        ]);
    }

    /**
     * The house catalogue — the products Snaker sells itself.
     */
    private function houseProducts(Request $request, int $limit): array
    {
        $products = Product::query()
            ->publiclyAvailable()
            ->with(['colors', 'sizes', 'images', 'videos', 'category', 'brand'])
            ->orderByDesc('is_pinned')
            ->latest('id')
            ->limit($limit)
            ->get();

        return ProductResource::collection($products)->resolve($request);
    }
}
