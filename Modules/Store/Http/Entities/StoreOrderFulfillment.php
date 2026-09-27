<?php

namespace Modules\Store\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Order\Http\Entities\Order;

class StoreOrderFulfillment extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'status' => 'awaiting',
        'penalty_amount' => 0,
    ];

    protected $casts = [
        'handover_due_at' => 'datetime',
        'handed_over_at' => 'datetime',
        'penalized_at' => 'datetime',
        'penalty_amount' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
