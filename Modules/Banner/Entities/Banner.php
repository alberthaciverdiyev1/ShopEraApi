<?php

namespace Modules\Banner\Entities;

use App\Traits\ImagePath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\Banner\Database\Factories\BannerFactory;
use Modules\Product\Entities\Product;
use Spatie\Translatable\HasTranslations;

class Banner extends Model
{
    use HasFactory,HasTranslations,ImagePath;

    protected $table = 'banners';

    /** @var array<int,string> */
    public array $translatable = ['title', 'subtitle'];

    protected $fillable = [
        'image',
        'second_image',
        'type',
        'is_active',
        'url',
        'product_id',
        'title',
        'subtitle',
    ];

    protected static function newFactory(): BannerFactory
    {
        return BannerFactory::new();
    }

    public function getSecondImageAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
