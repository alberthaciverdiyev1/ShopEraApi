<?php

namespace Modules\Manager\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeColor extends ControlModel
{
    protected $guarded = [];

    public function siteOwner(): BelongsTo
    {
        return $this->belongsTo(SiteOwner::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
