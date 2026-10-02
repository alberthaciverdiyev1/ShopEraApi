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

        $baseUrl = config('app.url') ?? request()->getSchemeAndHttpHost();

        return rtrim($baseUrl, '/').'/'.ltrim($value, '/');
    }
}
