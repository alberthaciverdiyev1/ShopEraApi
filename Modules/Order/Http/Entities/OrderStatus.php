<?php

namespace Modules\Order\Http\Entities;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    protected $table = 'order_statuses';

    protected $guarded = [];

    protected $casts = [
        'status' => \App\Enums\OrderStatus::class,
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function getLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     */
    public function scopeOfStatus($query,  \App\Enums\OrderStatus $status)
    {
        return $query->where('status', $status->value);
    }
}
