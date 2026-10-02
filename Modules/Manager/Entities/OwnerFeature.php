<?php

namespace Modules\Manager\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerFeature extends ControlModel
{
    protected $guarded = [];

    public function siteOwner(): BelongsTo
    {
        return $this->belongsTo(SiteOwner::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
