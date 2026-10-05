<?php

namespace Modules\CjDropShopping\Services;

/**
 * CJ Dropshipping category endpoints.
 */
class DCategoryService extends DBaseService
{
    /** Full CJ category tree. */
    public function categories(): array
    {
        return $this->get('product/getCategory');
    }
}
