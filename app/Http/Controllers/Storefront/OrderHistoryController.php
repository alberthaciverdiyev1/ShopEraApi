<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\DashboardService;
use Illuminate\Contracts\View\View;

/**
 * Full order history. Not linked from the navigation; the dashboard keeps its
 * recent-orders block.
 */
class OrderHistoryController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(): View
    {
        return view('storefront.orders.index', [
            'orders' => $this->dashboard->allOrders(),
        ]);
    }
}
