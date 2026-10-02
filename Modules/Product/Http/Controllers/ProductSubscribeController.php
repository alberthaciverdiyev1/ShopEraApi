<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Product;
use Modules\Product\Services\ProductSubscribeService;

class ProductSubscribeController extends Controller
{
    private ProductSubscribeService $service;

    public function __construct(ProductSubscribeService $service)
    {
        $this->service = $service;
    }

    /**
     * Subscribe to product stock notification
     */
    public function subscribe()
    {
        $user = Auth::user();
        $product_id = request()->get('product_id');

        try {
            $this->service->subscribe($user, $product_id);

            return responseHelper(__('We will notify you when product is back in stock.'),
                200,
                []
            );
        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Product not found.'),
                404,
                []
            );
        } catch (\Throwable $e) {
            return responseHelper(__('Something went wrong. Please try again later.'),
                500,
                []
            );
        }
    }

    /**
     * Unsubscribe from product stock notification
     */
    public function unsubscribe()
    {
        $user = Auth::user();
        (int) $product_id = request()->get('product_id');

        try {
            $this->service->subscribe($user, $product_id);

            return responseHelper(__('Unsubscribed successfully.'),
                200,
                []
            );
        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Product not found.'),
                404,
                []
            );
        } catch (\Throwable $e) {
            return responseHelper(__('Something went wrong. Please try again later.'),
                500,
                []
            );
        }
    }
}
