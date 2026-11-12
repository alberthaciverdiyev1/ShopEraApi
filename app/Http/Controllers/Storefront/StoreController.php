<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\CatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Marketplace\Services\VendorService;

class StoreController extends Controller
{
    public function __construct(
        private readonly VendorService $vendors,
        private readonly CatalogService $catalog,
    ) {}

    public function show(Request $request, string $slug): View
    {
        app()->setLocale('az');

        $vendor = $this->vendors->findBySlug($slug);

        abort_if($vendor === null, 404);

        $vendor = $this->vendors->payload($vendor);
        $result = $this->catalog->search(array_merge($request->query(), ['vendor_id' => $vendor['id']]));

        return view('storefront.store.show', [
            'vendor' => $vendor,
            'items' => $result['items'],
            'meta' => $result['meta'],
            'query' => $request->query(),
            'categories' => $this->catalog->categories(),
        ]);
    }
}
