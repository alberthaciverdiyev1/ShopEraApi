<?php

namespace Modules\Product\Http\Resources;

use App\Enums\Gender;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Brand\Http\Transformers\BrandResource;
use Modules\Category\Http\Transformers\CategoryResource;
use Modules\Color\Http\Transformers\ColorResource;
use Modules\Product\Services\ProductPricingService;
use Modules\Setting\Services\SettingService;
use Modules\Size\Http\Transformers\SizeResource;
use Modules\User\Http\UserResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = app()->getLocale();
        $pricingService = app(ProductPricingService::class);
        $prices = [
            'original_price' => $this->resolved_price,
            'discounted_price' => $this->resolved_discount,
        ];

        if ($prices['original_price'] === null) {
            $prices = $pricingService->prices($this->resource);
        }
        $wholesalePrices = $pricingService->wholesalePrices($this->resource);
        $lowStockThreshold = app(SettingService::class)->getPublicLowStockThreshold();
        $publicStockCount = (int) $this->stock_count <= $lowStockThreshold ? (int) $this->stock_count : null;

        return [
            'id' => $this->id,

            // A product can carry an empty translation for the active locale;
            // ucfirst(null) is deprecated on 8.3 and fatal later.
            'title' => ucfirst((string) $this->title),
            'description' => ucfirst((string) $this->description),

            'sku' => $this->sku,
            'is_favorite' => $this->is_favorite,
            'is_subscribe' => $this->is_subscribe,
            'is_suggest' => $this->is_suggest,
            'rate' => $this->rate,
            'rate_count' => (int) $this->rate_count,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'gender' => $this->gender !== null ? Gender::fromInt($this->gender)->label() : null,
            'price' => (float) $prices['original_price'],
            'wholesale_price' => $this->wholesale_price !== null ? (float) $this->wholesale_price : null,
            'effective_wholesale_price' => (float) $wholesalePrices['final_price'],
            'has_wholesale_price' => (bool) $wholesalePrices['has_wholesale_price'],
            'views' => (int) $this->views,
            //            'discount' => (float)($this->discount ?? 0),
            'discount' => (float) ($prices['discounted_price'] ?? 0),
            'discount_expire_date' => $this->discount_expire_date ? $this->discount_expire_date->format('Y-m-d H:i:s') : null,

            'stock_count' => (float) ($this->stock_count ?? 0),
            'public_stock_count' => $publicStockCount,
            'is_low_stock' => $publicStockCount !== null,
            'low_stock_threshold' => $lowStockThreshold,
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'purchase_limit' => $this->purchase_limit !== null ? (int) $this->purchase_limit : null,

            'is_active' => (bool) $this->is_active,
            'is_pinned' => (bool) $this->is_pinned,

            'sales_count' => (float) ($this->sales_count ?? 0),
            'title_admin' => $this->getTranslation('title', $locale, false) ?? $this->getTranslation('title', 'az'),
            'description_admin' => $this->getTranslation('description', $locale, false) ?? $this->getTranslation('description', 'az'),
            'colors' => ColorResource::collection($this->whenLoaded('colors')),

            // Values chosen from the dependent filter tree (brand/model/storage/…).
            'filter_values' => $this->whenLoaded('filterValues', fn () => $this->filterValues->map(fn ($fv) => [
                'filter_id' => $fv->filter_id,
                'filter' => $fv->filter?->title,
                'filter_value_id' => $fv->filter_value_id,
                'value' => $fv->value?->title,
            ])->values()),
            //            'sizes' => SizeResource::collection($this->whenLoaded('sizes')),

            'sizes' => $this->whenLoaded('sizes', function () {
                $pricingService = app(ProductPricingService::class);

                return $this->sizes->map(function ($size) use ($pricingService) {
                    $prices = $pricingService->prices($this->resource, $size->id);
                    $wholesalePrices = $pricingService->wholesalePrices($this->resource, $size->id);

                    return [
                        'id' => $size->id,
                        'name' => $size->name,
                        'price' => $prices['original_price'],
                        'wholesale_price' => $size->pivot?->wholesale_price !== null ? (float) $size->pivot->wholesale_price : null,
                        'effective_wholesale_price' => (float) $wholesalePrices['final_price'],
                        'has_wholesale_price' => (bool) $wholesalePrices['has_wholesale_price'],
                        'discount' => $prices['discounted_price'],
                    ];
                });
            }),

            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'videos' => ProductVideoResource::collection($this->whenLoaded('videos')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'user' => new UserResource($this->whenLoaded('user')),
            'approval_status' => $this->approval_status,
            'approved_at' => $this->approved_at ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s') : null,

            // When this product was last live, kept across edits and rejections
            // so a moderation queue can tell a returning product from a new one.
            'last_approved_at' => $this->last_approved_at ? Carbon::parse($this->last_approved_at)->format('Y-m-d H:i:s') : null,

            // Decided here rather than in the panel so every screen agrees, and
            // so the rule has one home if it ever gets subtler.
            'is_resubmission' => $this->approval_status === 'pending' && $this->last_approved_at !== null,

            'rejection_reason' => $this->rejection_reason,
            'deleted_at' => $this->deleted_at ? Carbon::parse($this->deleted_at)->format('Y-m-d H:i:s') : null,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),

            'filters' => $this->whenLoaded('productFilters', function () {
                $locale = app()->getLocale();

                return $this->productFilters->map(function ($pf) use ($locale) {
                    $filterTitle = $pf->filter?->getTranslation('title', $locale, false)
                        ?? $pf->filter?->getTranslation('title', 'az', false)
                        ?? (is_array($pf->filter?->title) ? reset($pf->filter->title) : $pf->filter?->title);

                    return [
                        'filter_id' => $pf->filter_id,
                        'name' => $filterTitle,
                        'title' => $filterTitle,
                        'value' => $pf->value,
                    ];
                })->filter(fn ($f) => ! empty($f['name']) && ! empty($f['value']))->values();
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            // The moderation queue needs "when was this sent to me", which for
            // an edited product is not when it was created.
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
