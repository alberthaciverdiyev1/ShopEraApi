<?php

namespace App\Http\Controllers\Api;

use App\Models\TenantPromoBlock;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Offer/ad blocks pushed from Manager.Snaker (Free-plan perk). Served from the
 * snapshot mirrored into this tenant's database, so the storefront never waits
 * on the Manager.
 */
class PromoBlockController extends Controller
{
    public function index()
    {
        try {
            $blocks = TenantPromoBlock::query()
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
            $blocks = [];
        }

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'OK',
            'data' => $blocks,
        ]);
    }
}
