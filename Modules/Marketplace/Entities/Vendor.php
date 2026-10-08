<?php

namespace Modules\Marketplace\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;
use Spatie\Translatable\HasTranslations;

/**
 * A seller's store. Optional: the same account may also sell is a plain user
 * (no store) or even as a guest. When a store exists, its products belong to
 * it and are sold through the normal order flow.
 */
class Vendor extends Model
{
    use HasTranslations, SoftDeletes;

    protected $table = 'vendors';

    protected $guarded = [];

    public array $translatable = ['name', 'description'];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUSPENDED = 'suspended';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
