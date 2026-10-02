<?php

namespace Modules\Manager\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends ControlModel
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function colors(): HasMany
    {
        return $this->hasMany(ThemeColor::class);
    }

    public function siteOwners(): HasMany
    {
        return $this->hasMany(SiteOwner::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
