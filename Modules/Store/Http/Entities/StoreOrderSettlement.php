<?php

namespace Modules\Store\Http\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Order\Http\Entities\Order;

class StoreOrderSettlement extends Model
{
    protected $guarded = [];

    /**
     * A settlement row is written the moment an order is placed — before the
     * card payment is even attempted. Most card baskets are then abandoned, so
     * `pending` is mostly a graveyard of orders that never happened.
     *
     * The seller is a partner, not a debugger: they should see money they have
     * earned and money that was taken back, not somebody's half-finished
     * basket. Admins keep the unfiltered view — they need `pending` to diagnose
     * stuck payments — which is why this is a named scope applied at the one
     * seller-facing endpoint rather than a global scope.
     */
    public function scopeVisibleToSeller(Builder $query): Builder
    {
        return $query->whereIn('status', ['settled', 'released', 'reversed']);
    }

    // DB defaults are not hydrated onto freshly created models, and the settle*()
    // guards compare against 'pending', so the default has to live here too.
    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'settled_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function walletTransaction()
    {
        return $this->belongsTo(StoreWalletTransaction::class);
    }
}
