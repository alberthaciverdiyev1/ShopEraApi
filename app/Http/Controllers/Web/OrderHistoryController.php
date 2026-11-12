<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Contracts\View\View;
use Modules\Order\Http\Controllers\OrderController;

/**
 * Full order history. Not linked from the navigation; the dashboard keeps its
 * recent-orders block.
 */
class OrderHistoryController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(): View
    {
        return view('storefront.orders.index', [
            'orders' => $this->payload(
                app(OrderController::class)->getAll($this->moduleHttpRequest())
            ),
        ]);
    }
}
