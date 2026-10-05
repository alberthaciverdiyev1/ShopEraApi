<?php

namespace Modules\CjDropShopping\Services;

/**
 * CJ Dropshipping logistics / freight endpoints.
 */
class DFreightService extends DBaseService
{
    /**
     * Calculate freight for a set of variants.
     *
     * @param  array{startCountryCode:string,endCountryCode:string,products:array<int,array{vid:string,quantity:int}>}  $payload
     */
    public function freight(array $payload): array
    {
        return $this->post('logistic/freightCalculate', $payload);
    }
}
