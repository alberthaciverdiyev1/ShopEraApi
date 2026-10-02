<?php

namespace Modules\Manager\Entities;

use Modules\Manager\Enums\OwnerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteOwner extends ControlModel
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'status' => OwnerStatus::class,
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function ownerFeatures(): HasMany
    {
        return $this->hasMany(OwnerFeature::class);
    }

    public function theme(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function themeColors(): HasMany
    {
        return $this->hasMany(ThemeColor::class);
    }

    public function suggestedTenantSlug(): string
    {
        $base = $this->primaryDomain()?->host ?? ('owner'.$this->id);
        $host = strtolower(preg_replace('/:\d+$/', '', $base));
        $parts = array_values(array_filter(explode('.', $host)));
        $slug = count($parts) > 2 && $parts[0] !== 'www' ? $parts[0] : $host;
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $slug), '-');

        return substr($slug !== '' ? $slug : 'owner'.$this->id, 0, 50);
    }

    /** Database name used by the shared ShopEra codebase for this tenant. */
    public function suggestedDbName(): string
    {
        return 'shopera_'.str_replace('-', '_', substr($this->tenantSlug(), 0, 48));
    }

    public function tenantSlug(): string
    {
        return (string) ($this->tenant_slug ?: $this->suggestedTenantSlug());
    }

    /** Public disk root, e.g. storage/app/public/redbull. */
    public function storageRoot(): string
    {
        return $this->tenantSlug();
    }

    public function primaryDomain(): ?Domain
    {
        return $this->domains->firstWhere('is_primary', true) ?? $this->domains->first();
    }

    public function scopeByHost($query, string $host)
    {
        return $query->whereHas('domains', fn ($q) => $q->where('host', $host));
    }
}
