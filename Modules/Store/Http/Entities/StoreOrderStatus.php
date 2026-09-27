<?php

namespace Modules\Store\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Order\Http\Entities\Order;
use Modules\User\Http\Entities\User;

class StoreOrderStatus extends Model
{
    protected $guarded = [];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
