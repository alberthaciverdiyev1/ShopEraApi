<?php

namespace App\Support;

use App\Models\TenantEntitlement;
use App\Models\TenantPromoBlock;
use App\Models\TenantSubscription;

/**
 * Persists a Manager.Snaker entitlements payload into the currently active
 * (tenant) database. Callers must set the target connection first — either via
 * ResolveTenant (web) or manager:push (cli).
 */
class EntitlementStore
{
    public static function persist(array $data, ?string $host = null): void
    {
        $now = now();
        $ownerId = $data['owner']['id'] ?? null;
        $subscription = (array) ($data['subscription'] ?? []);
        $host ??= (string) ($data['domain'] ?? '');

        // One snapshot per tenant database. A tenant can be synced through any
        // of its domains (custom domain or subdomain); keying on the owner id
        // avoids duplicate subscription rows when the host changes.
        $subscriptionKey = $ownerId !== null
            ? ['manager_owner_id' => $ownerId]
            : ['host' => (string) $host];

        TenantSubscription::query()->updateOrCreate(
            $subscriptionKey,
            [
                'host' => (string) $host,
                'manager_owner_id' => $ownerId,
                'plan_name' => $subscription['plan'] ?? null,
                'status' => $subscription['status'] ?? null,
                'price' => $subscription['price'] ?? null,
                'usable' => (bool) ($subscription['usable'] ?? true),
                'ends_at' => $subscription['ends_at'] ?? null,
                'synced_at' => $now,
            ]
        );

        $features = (array) ($data['features'] ?? []);
        $keys = [];

        foreach ($features as $key => $item) {
            $keys[] = $key;
            $value = is_array($item) ? ($item['value'] ?? null) : $item;

            TenantEntitlement::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value),
                    'type' => is_array($item) ? ($item['type'] ?? null) : null,
                    'source' => is_array($item) ? ($item['source'] ?? null) : null,
                    'synced_at' => $now,
                ]
            );
        }

        if ($keys !== []) {
            TenantEntitlement::query()->whereNotIn('key', $keys)->delete();
        }

        TenantPromoBlock::query()->delete();

        foreach (array_values((array) ($data['promo_blocks'] ?? [])) as $index => $block) {
            TenantPromoBlock::query()->create([
                'type' => $block['type'] ?? 'offer',
                'title' => $block['title'] ?? null,
                'subtitle' => $block['subtitle'] ?? null,
                'description' => $block['description'] ?? null,
                'image' => $block['image'] ?? null,
                'button_text' => $block['button_text'] ?? null,
                'url' => $block['url'] ?? null,
                'badge' => $block['badge'] ?? null,
                'sort_order' => $index,
            ]);
        }

        // The entitlement/subscription snapshots changed — drop the in-request
        // memos so subsequent reads see the fresh plan.
        Features::flush();
        Subscription::flush();
    }
}
