<?php

namespace Modules\Order\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StarexShipmentEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_date' => 'datetime',
        'payload' => 'array',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(StarexShipment::class, 'starex_shipment_id');
    }
}
