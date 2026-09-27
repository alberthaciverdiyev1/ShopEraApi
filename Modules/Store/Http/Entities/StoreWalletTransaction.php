<?php

namespace Modules\Store\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Order\Http\Entities\Order;
use Modules\User\Http\Entities\User;

class StoreWalletTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
