<?php

namespace App\Services\Storefront;

use Illuminate\Http\Request;
use Modules\Order\Services\OrderService;

/**
 * Read model for the account dashboard.
 */
class DashboardService
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentOrders(int $limit = 5): array
    {
        return array_slice($this->allOrders(), 0, $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allOrders(): array
    {
        return $this->orders
            ->getAll(Request::create('/api/order', 'GET'))
            ->getData(true)['data'] ?? [];
    }
}
