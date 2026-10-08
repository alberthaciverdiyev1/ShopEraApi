<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Brand\Entities\Brand;
use Modules\Category\Entities\Category;
use Modules\Color\Entities\Color;
use Modules\Filter\Entities\ProductFilter;
use Modules\Product\Database\Factories\ProductFactory;
use Modules\Size\Entities\Size;
use Modules\User\Entities\User;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $table = 'products';

    public array $translatable = ['title', 'description'];

    protected $guarded = [];

    /** Never leak the guest management token in API payloads. */
    protected $hidden = ['manage_token'];

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
        'promoted_until' => 'datetime',
        'premium_until' => 'datetime',
        'approved_at' => 'datetime',
        'last_approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopePubliclyAvailable($query)
    {
        return $query
            ->where('is_active', true)
            ->where('approval_status', 'approved');
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

    /** The store that sells this listing, when it was posted by a vendor. */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Marketplace\Entities\Vendor::class, 'vendor_id');
    }

    /** Where the listing is, for marketplace listings. */
    public function city(): BelongsTo
    {
        return $this->belongsTo(\Modules\Delivery\Entities\City::class, 'city_id');
    }

    public function productFilters(): HasMany
    {
        return $this->hasMany(ProductFilter::class, 'product_id');
    }

    /** Values chosen from the dependent filter tree (brand/model/storage/…). */
    public function filterValues(): HasMany
    {
        return $this->hasMany(\Modules\Filter\Entities\ProductFilterValue::class, 'product_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function getFallbackLocale(): string
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
            ->withPivot('notified_at', 'user_id', 'product_id')
            ->withTimestamps();
    }
}
