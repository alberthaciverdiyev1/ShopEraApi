<?php

namespace Modules\Store\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Http\Entities\User;

class MarketplaceAuditLog extends Model
{
    protected $table = 'marketplace_audit_logs';

    protected $guarded = [];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
