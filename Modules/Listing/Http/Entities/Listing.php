<?php

namespace Modules\Listing\Http\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Delivery\Http\Entities\City;
use Modules\User\Http\Entities\User;

/**
 * One ad. The answers to the section's fields live in listing_values, which is
 * what the filters read; attribute_values keeps a copy of the same answers so
 * a card or a page renders without joining them back together.
 */
class Listing extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
        self::STATUS_ARCHIVED,
    ];

    protected $table = 'listings';

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'previous_price' => 'decimal:2',
        'price_dropped_at' => 'datetime',
        'badges' => 'array',
        'is_negotiable' => 'boolean',
        'is_vip' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'attribute_values' => 'array',
        'views' => 'integer',
        'vip_until' => 'datetime',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewed_at' => 'datetime',
        'moderated_at' => 'datetime',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(ListingSection::class, 'section_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ListingValue::class, 'listing_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ListingMedia::class, 'listing_id')->orderBy('sort_order');
    }

    /** What a visitor may see: published, and not yet run out. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /** VIP is a flag with an end date, so an expired one stops lifting the ad. */
    public function isVip(): bool
    {
        return $this->is_vip && (! $this->vip_until || $this->vip_until->isFuture());
    }
}
