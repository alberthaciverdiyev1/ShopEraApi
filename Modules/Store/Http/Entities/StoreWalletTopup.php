<?php

namespace Modules\Store\Http\Entities;

use Illuminate\Database\Eloquent\Model;

class StoreWalletTopup extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'status' => 'waiting',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
