<?php

namespace Modules\Filter\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Http\Entities\Product;

class ProductFilter extends Model
{
    protected $table = 'product_filters';

    protected $guarded = [];

    public function filter(): BelongsTo
    {
        return $this->belongsTo(Filter::class, 'filter_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
