<?php

namespace Modules\Product\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Brand\Http\Entities\Brand;
use Modules\Category\Http\Entities\Category;
use Modules\Color\Http\Entities\Color;
use Modules\Product\Database\Factories\ProductFactory;
use Modules\Size\Http\Entities\Size;
use Modules\User\Http\Entities\User;
use Modules\Store\Http\Entities\Store;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations, HasFactory, SoftDeletes;

    protected $table = 'products';

    public array $translatable = ['title', 'description'];

    protected $guarded = [];

    // Mirrors the column default so a product created without an explicit
    // approval_status does not serialize as null before it is reloaded.
    protected $attributes = [
        'approval_status' => 'approved',
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'stock_count' => 'integer',
        'weight' => 'float',
        'discount_expire_date' => 'datetime',
    ];

    public function scopePubliclyAvailable($query)
    {
        return $query
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->where(function ($query) {
                $query->whereNull('store_id');

                // The admin kill switch hides every marketplace product at once
                // without touching a single store record.
                if (app(\Modules\Setting\Services\SettingService::class)->isMarketplaceEnabled()) {
                    $query->orWhereHas('store', fn ($storeQuery) => $storeQuery
                        ->where('status', 'approved')
                        ->where('is_active', true));
                }
            });
    }

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'color_product');
    }

    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class, 'product_size')->withPivot('price', 'wholesale_price', 'discount');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function productFilters(): HasMany
    {
        return $this->hasMany(\Modules\Filter\Http\Entities\ProductFilter::class, 'product_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function getFallbackLocale() : string
    {
        return config('product.fallback_locale', 'az');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorites', 'product_id', 'user_id');
    }

    public function subscribedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'product_stock_subscriptions', 'product_id', 'user_id');
    }

    public function stockSubscribers()
    {
        return $this->belongsToMany(User::class, 'product_stock_subscriptions')
            ->withPivot('notified_at','user_id','product_id')
            ->withTimestamps();
    }

}
