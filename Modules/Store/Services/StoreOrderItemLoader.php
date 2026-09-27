<?php

namespace Modules\Store\Services;

use Illuminate\Support\Collection;
use Modules\Order\Http\Entities\OrderItem;

/**
 * Loads the order lines that belong to a given store, grouped by order id, so
 * merchant and admin listings can show what was actually purchased without
 * running a query per row.
 */
class StoreOrderItemLoader
{
    /**
     * @param  array<int, int>  $orderIds
     * @param  array<int, int>|int|null  $storeIds  restrict to these stores; null = every store
     * @return Collection<int, Collection<int, OrderItem>>
     */
    public function forOrders(array $orderIds, array|int|null $storeIds = null): Collection
    {
        if (empty($orderIds)) {
            return collect();
        }

        $storeIds = $storeIds === null ? null : (array) $storeIds;

        return OrderItem::with(['product.images', 'color:id,name', 'size:id,name'])
            ->whereIn('order_id', $orderIds)
            ->when($storeIds !== null, fn ($q) => $q->whereHas(
                'product',
                fn ($p) => $p->whereIn('store_id', $storeIds)
            ))
            ->get()
            ->groupBy('order_id');
    }

    /**
     * Items of one order that belong to one store.
     *
     * @return Collection<int, OrderItem>
     */
    public function forOrder(int $orderId, ?int $storeId = null): Collection
    {
        return $this->forOrders([$orderId], $storeId)->get($orderId, collect());
    }
}
