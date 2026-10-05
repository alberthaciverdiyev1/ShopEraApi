<?php

namespace Modules\CjDropShopping\Services;

/**
 * CJ Dropshipping product endpoints (search/list + detail).
 */
class DProductService extends DBaseService
{
    /**
     * Search/list products.
     *
     * Supported filters (CJ names): pageNum, pageSize, categoryId,
     * productNameEn, countryCode, startSellPrice, endSellPrice.
     */
    public function products(array $filters = []): array
    {
        return $this->get('product/list', array_filter(
            $filters,
            fn ($value) => $value !== null && $value !== '',
        ));
    }

    /** Fetch a single product by its CJ product id (pid). */
    public function product(string $pid): array
    {
        return $this->get('product/query', ['pid' => $pid]);
    }
}
