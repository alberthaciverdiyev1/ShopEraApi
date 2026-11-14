<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Color\Entities\Color;
use Modules\Size\Entities\Size;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'color_id',
        'size_id',
        'price',
        'discount',
        'wholesale_price',
        'stock',
    ];

    protected $casts = [
        'color_id' => 'integer',
        'size_id' => 'integer',
        'price' => 'float',
        'discount' => 'float',
        'wholesale_price' => 'float',
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
