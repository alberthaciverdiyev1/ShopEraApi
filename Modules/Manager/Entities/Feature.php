<?php

namespace Modules\Manager\Entities;

use Modules\Manager\Enums\FeatureType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends ControlModel
{
    protected $guarded = [];

    protected $casts = [
        'type' => FeatureType::class,
    ];

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_features')->withPivot('value')->withTimestamps();
    }
}
