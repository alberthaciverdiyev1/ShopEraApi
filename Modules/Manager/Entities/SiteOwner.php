<?php

namespace Modules\Manager\Entities;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Manager\Enums\OwnerStatus;

class SiteOwner extends ControlModel
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'status' => OwnerStatus::class,
        'last_login_at' => 'datetime',
        'last_logout_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'usage_reported_at' => 'datetime',
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OwnerActivity::class)->latest('created_at');
    }

    /**
     * The owner that backs the tenant of the current request (resolved by the
     * active tenant database, then by host). Null on non-tenant requests.
     */
    public static function findForCurrentTenant(): ?self
    {
        $database = TenantContext::database();
        if (is_string($database) && $database !== '') {
            $owner = static::query()->where('db_name', $database)->first();
            if ($owner) {
                return $owner;
            }
        }

        $host = request()?->getHost();
        if (is_string($host) && $host !== '') {
            return static::query()->byHost($host)->first();
        }

        return null;
    }

    /** Touches last_seen (+ action) and writes an activity row. */
    public function touchActivity(string $action, ?string $description = null, ?string $ip = null): void
    {
        try {
            $this->forceFill(['last_seen_at' => now(), 'last_action' => $action])->save();
        } catch (\Throwable) {
            // ignore
        }

        OwnerActivity::record($this, $action, $description, $ip);
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

    public function theme(): BelongsTo
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
