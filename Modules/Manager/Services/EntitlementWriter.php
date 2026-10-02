<?php

namespace Modules\Manager\Services;

use App\Support\EntitlementStore;
use App\Support\TenantDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Manager\Entities\PromoBlock;
use Modules\Manager\Entities\SiteOwner;

/**
 * Replaces the old HTTP "manager:sync": resolves a site owner's effective
 * entitlements/theme from the control database and writes them straight into
 * that owner's tenant database(s). No token, no webhook.
 */
class EntitlementWriter
{
    public function __construct(
        private readonly FeatureService $features,
        private readonly ThemeService $theme,
    ) {}

    /** @return array<int,string> hosts actually written */
    public function push(SiteOwner $owner, ?string $host = null): array
    {
        $owner->loadMissing(['domains', 'currentSubscription.plan.features', 'ownerFeatures.feature', 'theme']);

        $hosts = $owner->domains->pluck('host')->filter()->all();
        if ($host !== null) {
            $hosts = array_values(array_intersect($hosts, [$host]));
        }

        $previous = DB::getDefaultConnection();
        $pushed = [];

        try {
            foreach ($hosts as $h) {
                $database = $owner->db_name ?: TenantDatabase::nameFor($h);

                if (! TenantDatabase::exists($database)) {
                    continue;
                }

                config(['database.connections.tenant.database' => $database]);
                DB::purge('tenant');
                DB::setDefaultConnection('tenant');

                $payload = $this->payload($owner, $h);

                EntitlementStore::persist($payload, $h);

                // Mirror the resolved palette into this tenant's ThemeColor rows
                // so the storefront's own /api/theme keeps working.
                app(\App\Http\Controllers\Admin\ThemeController::class)->storePalette($payload['theme'] ?? []);

                $pushed[] = $h;
            }
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('tenant');
        }

        return $pushed;
    }

    /** @return array<string,mixed> payload shape consumed by EntitlementStore */
    public function payload(SiteOwner $owner, string $host): array
    {
        $subscription = $owner->currentSubscription;

        return [
            'owner' => [
                'id' => $owner->id,
                'name' => $owner->name,
                'status' => $owner->status?->value,
            ],
            'domain' => $host,
            'subscription' => [
                'status' => $subscription?->status?->value,
                'plan' => $subscription?->plan?->name,
                'price' => $subscription?->price,
                'ends_at' => $subscription?->ends_at?->toIso8601String(),
                'usable' => (bool) $subscription?->status?->isUsable(),
            ],
            'features' => $this->features->resolve($owner),
            'theme' => $this->theme->forOwner($owner),
            'promo_blocks' => $this->promoBlocks($owner),
        ];
    }

    private function promoBlocks(SiteOwner $owner): array
    {
        $features = $this->features->resolve($owner);
        $enabled = in_array(strtolower((string) ($features['promo_blocks']['value'] ?? '')), ['1', 'true', 'yes', 'on'], true);

        if (! $enabled) {
            return [];
        }

        return PromoBlock::query()->active()->orderBy('sort_order')->get()->map(fn (PromoBlock $b) => [
            'id' => $b->id,
            'type' => $b->type,
            'title' => $b->title,
            'subtitle' => $b->subtitle,
            'description' => $b->description,
            'image' => $b->image,
            'button_text' => $b->button_text,
            'url' => $b->url,
            'badge' => $b->badge,
        ])->all();
    }
}
