<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Services\ProductService;

/**
 * The home screen in one call: ShopEra's own catalogue.
 */
class HomeController extends Controller
{
    public function __construct(private readonly ProductService $service) {}

    public function index(Request $request)
    {
        $productLimit = min(max((int) $request->input('product_limit', 20), 1), 50);

        return responseHelper('Home retrieved successfully.', 200, [
            'house_products' => $this->service->houseProducts($request, $productLimit),
        ]);
    }
}
