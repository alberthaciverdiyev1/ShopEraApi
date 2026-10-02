<?php

namespace Modules\Manager\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends ControlModel
{
    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'ssl_enabled' => 'boolean',
    ];

    public function siteOwner(): BelongsTo
    {
        return $this->belongsTo(SiteOwner::class);
    }
}
