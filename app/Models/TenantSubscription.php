<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSubscription extends Model
{
    protected $table = 'tenant_subscriptions';

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'usable' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'synced_at' => 'datetime',
    ];
}
