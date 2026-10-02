<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantEntitlement extends Model
{
    protected $table = 'tenant_entitlements';

    protected $guarded = [];

    protected $casts = [
        'synced_at' => 'datetime',
    ];
}
