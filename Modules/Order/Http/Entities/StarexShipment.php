<?php

namespace Modules\Order\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StarexShipment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_event_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(StarexShipmentEvent::class);
    }
}
