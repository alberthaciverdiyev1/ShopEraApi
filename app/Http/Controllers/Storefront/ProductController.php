<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\CatalogService;
use App\Services\Storefront\ProductDetailService;
use Illuminate\Http\Request;
use Modules\Marketplace\Services\ListingService;
use Modules\Product\Entities\Product;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductDetailService $details,
        private readonly CatalogService $catalog,
    ) {}

    public function show(Request $request)
    {
        $id = (int) $request->query('id');

        if ($id <= 0) {
            abort(404);
        }

        return $this->render($this->details->byId($id));
    }

    public function showBySlug(string $slug)
    {
        return $this->render($this->details->bySlug($slug));
    }

    /**
     * Seller contact is public in the API too; the Blade page reveals it with
     * a same-origin request so the number is never written into the HTML.
     */
    public function contact(int $id, ListingService $listings)
    {
        $listing = Product::query()->publiclyAvailable()->findOrFail($id);

        return response()->json($listings->contact($listing));
    }

    private function render(?array $product)
    {
        abort_if($product === null, 404);

        app()->setLocale('az');

        $similar = $this->details->similar($product);

        return view('storefront.product.show', [
            'product' => $product,
            'similar' => $similar,
            'categories' => $this->catalog->categories(),
        ]);
    }
}
