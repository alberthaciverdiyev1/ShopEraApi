<?php

namespace Modules\Size\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Http\Entities\Product;
use Modules\Size\Database\Factories\SizeFactory;
use Spatie\Translatable\HasTranslations;

class Size extends Model
{
    use SoftDeletes, HasFactory, HasTranslations;
    protected $table = 'sizes';

    public array $translatable = ['name'];

    protected $guarded = [];

    protected $casts = [
        'name' => 'array',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
    public static function newFactory(): SizeFactory
    {
        return SizeFactory::new();
    }

    public function getIconAttribute($value): ?string
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
