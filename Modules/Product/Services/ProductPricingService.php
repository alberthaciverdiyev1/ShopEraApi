<?php

namespace Modules\Product\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Product\Http\Entities\Product;

class ProductPricingService
{
    public function isWholesale($user = null): bool
    {
        return false;
    }

    public function prices(Product $product, ?int $sizeId = null, $user = null, string $pricingType = 'retail'): array
    {
        return $pricingType === 'wholesale'
            ? $this->wholesalePrices($product, $sizeId)
            : $this->retailPrices($product, $sizeId);
    }

    public function retailPrices(Product $product, ?int $sizeId = null): array
    {
        $selectedSize = $sizeId !== null
            ? $product->sizes->firstWhere('id', $sizeId)
            : null;

        $retailPrice = $selectedSize?->pivot?->price
            ?? $product->price
            ?? $this->minimumPivotValue($product, 'price')
            ?? 0;

        $discountedPrice = $selectedSize?->pivot?->discount
            ?? $product->discount
            ?? ($sizeId === null ? $this->minimumPivotValue($product, 'discount') : null)
            ?? 0;

        $retailPrice = (float)$retailPrice;
        $discountedPrice = (float)$discountedPrice;
        $hasValidDiscount = $discountedPrice > 0
            && $discountedPrice < $retailPrice
            && $this->hasActiveDiscount($product);

        return [
            'original_price' => $retailPrice,
            'discounted_price' => $hasValidDiscount ? $discountedPrice : 0.0,
            'final_price' => $hasValidDiscount ? $discountedPrice : $retailPrice,
            'pricing_type' => 'retail',
        ];
    }

    public function wholesalePrices(Product $product, ?int $sizeId = null): array
    {
        $selectedSize = $sizeId !== null
            ? $product->sizes->firstWhere('id', $sizeId)
            : null;

        $retailPrices = $this->retailPrices($product, $sizeId);
        $rawWholesalePrice = $selectedSize?->pivot?->wholesale_price
            ?? ($sizeId === null ? $this->minimumPivotValue($product, 'wholesale_price') : null)
            ?? $product->wholesale_price;

        $wholesalePrice = $rawWholesalePrice ?? $retailPrices['final_price'];

        return [
            'original_price' => (float)$retailPrices['original_price'],
            'discounted_price' => 0.0,
            'final_price' => (float)$wholesalePrice,
            'pricing_type' => 'wholesale',
            'wholesale_price' => (float)$wholesalePrice,
            'has_wholesale_price' => $rawWholesalePrice !== null,
            'retail_final_price' => (float)$retailPrices['final_price'],
        ];
    }

    public function basketTotal(Collection $basket, $user = null, string $pricingType = 'retail'): float
    {
        return round($basket->sum(function ($item) use ($user, $pricingType) {
            return $item->quantity * $this->prices($item->product, $item->size_id, $user, $pricingType)['final_price'];
        }), 2);
    }

    public function basketPricingSummary(Collection $basket, float $wholesaleMinimalPurchasePrice): array
    {
        $originalTotal = 0.0;
        $retailTotal = 0.0;
        $wholesaleTotal = 0.0;

        foreach ($basket as $item) {
            $quantity = (int)$item->quantity;
            $retailPrices = $this->retailPrices($item->product, $item->size_id);
            $wholesalePrices = $this->wholesalePrices($item->product, $item->size_id);

            $originalTotal += $retailPrices['original_price'] * $quantity;
            $retailTotal += $retailPrices['final_price'] * $quantity;
            $wholesaleTotal += $wholesalePrices['final_price'] * $quantity;
        }

        $originalTotal = round($originalTotal, 2);
        $retailTotal = round($retailTotal, 2);
        $wholesaleTotal = round($wholesaleTotal, 2);
        $isWholesaleApplied = $wholesaleMinimalPurchasePrice <= 0
            ? $wholesaleTotal > 0
            : $wholesaleTotal >= $wholesaleMinimalPurchasePrice;
        $finalTotal = $isWholesaleApplied ? $wholesaleTotal : $retailTotal;

        return [
            'pricing_type' => $isWholesaleApplied ? 'wholesale' : 'retail',
            'is_wholesale_applied' => $isWholesaleApplied,
            'wholesale_minimal_purchase_price' => round($wholesaleMinimalPurchasePrice, 2),
            'wholesale_remaining_amount' => round(max($wholesaleMinimalPurchasePrice - $wholesaleTotal, 0), 2),
            'original_total' => $originalTotal,
            'retail_total' => $retailTotal,
            'wholesale_total' => $wholesaleTotal,
            'final_total' => round($finalTotal, 2),
            'discount_amount' => round(max($originalTotal - $finalTotal, 0), 2),
        ];
    }

    private function minimumPivotValue(Product $product, string $field): ?float
    {
        if (!$product->relationLoaded('sizes') || $product->sizes->isEmpty()) {
            return null;
        }

        $value = $product->sizes
            ->pluck("pivot.{$field}")
            ->filter(fn ($price) => $price !== null)
            ->min();

        return $value !== null ? (float)$value : null;
    }

    private function hasActiveDiscount(Product $product): bool
    {
        return !$product->discount_expire_date
            || Carbon::parse($product->discount_expire_date)->isFuture();
    }
}
