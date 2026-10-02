<?php

namespace Modules\Manager\Services;

use Modules\Manager\Entities\Feature;
use Modules\Manager\Entities\SiteOwner;

/**
 * Resolves the effective entitlements for a site owner.
 * Priority: owner override > current plan > feature default.
 */
class FeatureService
{
    private const TRUTHY = ['1', 'true', 'yes', 'on', 'enabled'];

    /** @return array<string,array{value:?string,type:string,source:string}> */
    public function resolve(SiteOwner $owner): array
    {
        $subscription = $owner->currentSubscription;
        $planValues = $subscription?->plan
            ? $subscription->plan->features->mapWithKeys(fn ($f) => [$f->key => $f->pivot->value])->all()
            : [];

        $overrides = $owner->ownerFeatures()->with('feature')->get()
            ->mapWithKeys(fn ($of) => [$of->feature->key => $of->value])
            ->all();

        $out = [];
        foreach (Feature::query()->orderBy('sort_order')->get() as $feature) {
            if (array_key_exists($feature->key, $overrides)) {
                [$value, $source] = [$overrides[$feature->key], 'override'];
            } elseif (array_key_exists($feature->key, $planValues)) {
                [$value, $source] = [$planValues[$feature->key], 'plan'];
            } else {
                [$value, $source] = [$feature->default_value, 'default'];
            }

            $out[$feature->key] = [
                'value' => $value,
                'type' => $feature->type->value,
                'source' => $source,
            ];
        }

        return $out;
    }

    public function enabled(SiteOwner $owner, string $key): bool
    {
        $value = $this->resolve($owner)[$key]['value'] ?? null;

        return in_array(strtolower((string) $value), self::TRUTHY, true);
    }

    public function limit(SiteOwner $owner, string $key): ?int
    {
        $value = $this->resolve($owner)[$key]['value'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /** @return array<string,?string> key => value, for the API. */
    public function flat(SiteOwner $owner): array
    {
        return collect($this->resolve($owner))->map(fn ($item) => $item['value'])->all();
    }
}
