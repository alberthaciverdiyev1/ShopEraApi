<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    // use SoftDeletes;

    protected $table = 'product_image';

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImagePathAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // Build the URL from the host that served the request instead of the
        // configured APP_URL, so files uploaded in one environment (for
        // example the local dev server) are served by that same environment.
        return url('/storage/'.ltrim($value, '/'));
    }
}
