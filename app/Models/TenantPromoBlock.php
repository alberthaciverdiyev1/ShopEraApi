<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantPromoBlock extends Model
{
    protected $table = 'tenant_promo_blocks';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
