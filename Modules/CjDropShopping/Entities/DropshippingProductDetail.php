<?php

namespace Modules\CjDropShopping\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Entities\Product;

class DropshippingProductDetail extends Model
{
    protected $table = 'dropshipping_product_details';

    protected $guarded = [];

    protected $casts = [
        'shipping_country_codes' => 'array',
        'variants' => 'array',
        'raw' => 'array',
        'raw_my' => 'array',
        'is_free_shipping' => 'boolean',
        'weight_grams' => 'float',
        'pack_weight_grams' => 'float',
        'cj_discount_price' => 'float',
        'listed_num' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
