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

    /**
     * Products the merchant added to their own CJ panel ("My Products"),
     * paginated. Returns the raw payload: pageSize, pageNumber, totalRecords,
     * totalPages, content[] (each item has productId/pid, vid, nameEn, sku,
     * bigImage, sellPrice, weight, ...).
     */
    public function myProducts(int $pageNum = 1, int $pageSize = 50): array
    {
        return $this->get('product/myProduct/query', [
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
        ]);
    }
}
