<?php

namespace Modules\Size\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Entities\Product;
use Modules\Size\Database\Factories\SizeFactory;
use Spatie\Translatable\HasTranslations;

class Size extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

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
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }
}
