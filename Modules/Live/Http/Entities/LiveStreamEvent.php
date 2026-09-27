<?php

namespace Modules\Live\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Http\Entities\Product;
use Modules\User\Http\Entities\User;

class LiveStreamEvent extends Model
{
    public const TYPE_PRODUCT_CLICK = 'product_click';
    public const TYPE_ADD_TO_CART = 'add_to_cart';
    public const TYPE_LIKE = 'like';
    public const TYPE_ORDER = 'order';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
