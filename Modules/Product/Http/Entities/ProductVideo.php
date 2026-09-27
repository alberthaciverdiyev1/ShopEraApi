<?php

namespace Modules\Product\Http\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductVideo extends Model
{
    protected $table = 'product_videos';
    protected $guarded = [];

    protected $casts = [
        'story_expires_at' => 'datetime',
        'is_story_hidden' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function getVideoPathAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        $baseUrl = config('app.url') ?? request()->getSchemeAndHttpHost();

        return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
    }
}
