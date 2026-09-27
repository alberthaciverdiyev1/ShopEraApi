<?php

namespace Modules\Store\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Services\StoreProfileService;

/**
 * What a shopper can see about a store. No authentication: these mirror the
 * public catalogue and expose nothing beyond it.
 */
class StorePublicController extends Controller
{
    public function __construct(private StoreProfileService $profiles) {}

    public function show(int $id)
    {
        return responseHelper(__('Store retrieved successfully.'), 200, $this->profiles->summary($this->visibleStore($id)));
    }

    public function products(Request $request, int $id)
    {
        $store = $this->visibleStore($id);
        $items = $this->profiles->products(
            $store,
            $request->all(),
            min(max((int) $request->input('per_page', 20), 1), 100)
        );

        return responseHelper(__('Store products retrieved successfully.'), 200,
            ProductResource::collection($items)->response()->getData(true));
    }

    public function reviews(Request $request, int $id)
    {
        $store = $this->visibleStore($id);

        return responseHelper(__('Reviews retrieved successfully.'), 200,
            $this->profiles->reviews($store, min(max((int) $request->input('per_page', 20), 1), 100)));
    }

    /** Only an approved, active store has a public page. */
    private function visibleStore(int $id): Store
    {
        return Store::where('status', 'approved')->where('is_active', true)->findOrFail($id);
    }
}
