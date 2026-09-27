<?php

namespace Modules\Banner\Http\Entities;

use App\Traits\ImagePath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Banner\Database\Factories\BannerFactory;

class Banner extends Model
{
    use HasFactory,ImagePath;

    protected $table = 'banners';

    protected $fillable = [
        'image',
        'second_image',
        'type',
        'is_active',
        'url'
    ];

    protected static function newFactory(): BannerFactory
    {
        return BannerFactory::new();
    }

    public function getSecondImageAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }
}
