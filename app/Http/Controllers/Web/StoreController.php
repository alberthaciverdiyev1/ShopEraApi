<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Marketplace\Http\Controllers\VendorController;

class StoreController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(Request $request): View
    {
        app()->setLocale('az');

        $data = (array) $this->payload(
            app(VendorController::class)->index($this->moduleHttpRequest([
                'page' => $request->query('page', 1),
            ]))
        );

        return view('storefront.store.index', [
            'vendors' => $data['vendors'] ?? [],
            'meta' => $data['meta'] ?? [],
            'categories' => $this->storefrontCategories(),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        app()->setLocale('az');

        $vendor = $this->payload(app(VendorController::class)->show($slug));

        abort_if(! is_array($vendor) || $vendor === [], 404);

        $result = $this->searchProducts(array_merge($request->query(), ['vendor_id' => $vendor['id']]));

        return view('storefront.store.show', [
            'vendor' => $vendor,
            'items' => $result['items'],
            'meta' => $result['meta'],
            'query' => $request->query(),
            'categories' => $this->storefrontCategories(),
        ]);
    }
}
