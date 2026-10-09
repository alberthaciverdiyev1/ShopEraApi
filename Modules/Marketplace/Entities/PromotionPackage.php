<?php

namespace Modules\Marketplace\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PromotionPackage extends Model
{
    use HasTranslations;

    protected $table = 'promotion_packages';

    protected $guarded = [];

    public array $translatable = ['name'];

    protected $casts = [
        'name' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public const TYPE_PROMOTED = 'promoted';

    public const TYPE_VIP = 'vip';

    public const TYPE_PREMIUM = 'premium';

    /** Every placement type a package (and a listing_promotion) can carry. */
    public const TYPES = [self::TYPE_PROMOTED, self::TYPE_VIP, self::TYPE_PREMIUM];
}
