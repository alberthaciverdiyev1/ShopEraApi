<?php

namespace Modules\Manager\Entities;

use Modules\Manager\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends ControlModel
{
    protected $guarded = [];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'price' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function siteOwner(): BelongsTo
    {
        return $this->belongsTo(SiteOwner::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
