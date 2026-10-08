<?php

namespace Modules\Marketplace\Services;

use Illuminate\Validation\ValidationException;
use Modules\Marketplace\Entities\ListingPromotion;
use Modules\Marketplace\Entities\PromotionPackage;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

class PromotionService
{
    /** Active packages a seller can buy. */
    public function packages(): \Illuminate\Support\Collection
    {
        return PromotionPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('price')
            ->get();
    }

    /** Create a pending promotion order for a listing. */
    public function createOrder(Product $listing, User $user, PromotionPackage $package): ListingPromotion
    {
        if ((int) $listing->user_id !== (int) $user->id) {
            throw ValidationException::withMessages(['listing' => 'Bu elan sizə aid deyil.']);
        }

        return ListingPromotion::query()->create([
            'product_id' => $listing->id,
            'package_id' => $package->id,
            'user_id' => $user->id,
            'type' => $package->type,
            'price' => $package->price,
            'status' => 'pending',
        ]);
    }

    /** Mark an order paid and apply the promotion to the listing. */
    public function activate(ListingPromotion $promotion): void
    {
        $days = $promotion->package?->days ?? 7;
        $starts = now();
        $ends = $starts->copy()->addDays($days);

        $promotion->forceFill([
            'status' => 'paid',
            'starts_at' => $starts,
            'ends_at' => $ends,
        ])->save();

        $product = $promotion->product;
        if (! $product) {
            return;
        }

        if ($promotion->type === PromotionPackage::TYPE_PREMIUM) {
            $product->forceFill(['is_premium' => true, 'premium_until' => $ends])->save();
        } else {
            $product->forceFill(['is_promoted' => true, 'promoted_until' => $ends])->save();
        }
    }

    public function cancel(ListingPromotion $promotion): void
    {
        $promotion->forceFill(['status' => 'cancelled'])->save();
    }

    /** Clear expired promotions (called on a schedule). */
    public function expire(): int
    {
        $promoted = Product::query()
            ->where('is_promoted', true)
            ->whereNotNull('promoted_until')->where('promoted_until', '<', now())
            ->update(['is_promoted' => false]);

        $premium = Product::query()
            ->where('is_premium', true)
            ->whereNotNull('premium_until')->where('premium_until', '<', now())
            ->update(['is_premium' => false]);

        return $promoted + $premium;
    }
}
