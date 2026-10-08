<?php

namespace Modules\Filter\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Entities\Product;

class ProductFilterValue extends Model
{
    protected $table = 'product_filter_values';

    protected $guarded = [];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function filter(): BelongsTo
    {
        return $this->belongsTo(Filter::class, 'filter_id');
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(FilterValue::class, 'filter_value_id');
    }
}
