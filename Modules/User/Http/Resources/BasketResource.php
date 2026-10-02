<?php

namespace Modules\User\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Http\Resources\ProductImageResource;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Services\ProductPricingService;

class BasketResource extends JsonResource
{
    public function toArray($request)
    {
        $colorId = $this->color_id;
        $product = $this->whenLoaded('product') ? $this->product : null;
        $retailPrices = null;
        $wholesalePrices = null;

        $productData = null;
        if ($product) {
            $pricingService = app(ProductPricingService::class);
            $retailPrices = $pricingService->retailPrices($product, $this->size_id);
            $wholesalePrices = $pricingService->wholesalePrices($product, $this->size_id);
            $productData = (new ProductResource($product))->toArray($request);

            if ($product->relationLoaded('images') && $colorId) {
                $colorImages = $product->images->where('color_id', $colorId)->values();
                $images = $colorImages->isNotEmpty()
                    ? $colorImages
                    : $product->images;
            } else {
                $images = $product->relationLoaded('images') ? $product->images : collect();
            }

            $productData['images'] = ProductImageResource::collection($images)->toArray($request);
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'color_id' => $this->color_id,
            'size_id' => $this->size_id,
            'gender' => $this->gender,
            'selected' => $this->selected,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'retail_unit_price' => $retailPrices !== null ? round($retailPrices['final_price'], 2) : null,
            'wholesale_unit_price' => $wholesalePrices !== null ? round($wholesalePrices['final_price'], 2) : null,
            'retail_total' => $retailPrices !== null ? round($retailPrices['final_price'] * (int) $this->quantity, 2) : null,
            'wholesale_total' => $wholesalePrices !== null ? round($wholesalePrices['final_price'] * (int) $this->quantity, 2) : null,
            'has_wholesale_price' => $wholesalePrices !== null ? (bool) $wholesalePrices['has_wholesale_price'] : false,
            'product' => $productData,
        ];
    }
}
