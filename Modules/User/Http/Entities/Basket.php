<?php

namespace Modules\User\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Log;
use Modules\Product\Http\Entities\Product;

class Basket extends Model
{
    protected $table = 'baskets';
    protected $guarded = [];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        static::deleting(function ($basket) {
            if ($basket->user_id === 41) {

                Log::error("BASKET IS DELETING!", [
                    'basket_id' => $basket->id,
                    'user_id' => $basket->user_id,
                    'product_id' => $basket->product_id,
                    'is_ordered' => $basket->is_ordered,
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
                ]);
            }
        });
    }
}
