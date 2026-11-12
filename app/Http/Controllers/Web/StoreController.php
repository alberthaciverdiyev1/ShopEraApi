<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Marketplace\Http\Controllers\VendorController;

class StoreController extends Controller
{
    use InteractsWithModuleControllers;

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
