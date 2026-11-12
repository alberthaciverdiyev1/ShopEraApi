<?php

namespace Modules\Setting\Services;

use Illuminate\Support\Facades\Log;
use Modules\Setting\Entities\TenantPromoBlock;

/**
 * Offer/ad blocks shown on the storefront home and shop pages.
 */
class PromoBlockService
{
    public function all(): array
    {
        try {
            return TenantPromoBlock::query()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (TenantPromoBlock $block) => [
                    'id' => $block->id,
                    'type' => $block->type,
                    'title' => $block->title,
                    'subtitle' => $block->subtitle,
                    'description' => $block->description,
                    'image' => $block->image,
                    'button_text' => $block->button_text,
                    'url' => $block->url,
                    'badge' => $block->badge,
                ])
                ->all();
        } catch (\Throwable $e) {
            // Table missing (fresh install / unmigrated) — serve nothing.
            Log::warning('Tenant promo blocks unavailable; serving none.', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
