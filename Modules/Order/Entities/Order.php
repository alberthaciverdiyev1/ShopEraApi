<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\PromoCode\Entities\PromoCode;
use Modules\User\Entities\Address;
use Modules\User\Entities\User;

class Order extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function statuses()
    {
        return $this->hasMany(OrderStatus::class, 'order_id', 'id');
    }

    public function latestStatus()
    {
        return $this->hasOne(OrderStatus::class, 'order_id', 'id')->latestOfMany();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id', 'id');
    }

    // Order.php
    public function promoCodes()
    {
        return $this->hasManyThrough(
            PromoCode::class,
            Pivot::class,
            'order_id', // used_promo_codes.order_id
            'id',       // promo_codes.id
            'id',       // orders.id
            'promo_code_id' // used_promo_codes.promo_code_id
        );
    }
}
