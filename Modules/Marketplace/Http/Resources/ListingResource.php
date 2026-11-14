<?php

namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'discount' => $this->discount !== null ? (float) $this->discount : null,
            'stock_count' => (int) $this->stock_count,
            'seller_type' => $this->seller_type,
            'condition' => $this->condition,
            'has_delivery' => (bool) $this->has_delivery,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand?->name),
            'model' => $this->model,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => $this->city?->name),
            'colors' => $this->whenLoaded('colors', fn () => $this->colors->map(fn ($color) => [
                'id' => $color->id,
                'name' => $color->name,
                'hex' => $color->hex,
            ])),
            'sizes' => $this->whenLoaded('sizes', fn () => $this->sizes->map(fn ($size) => [
                'id' => $size->id,
                'name' => $size->name,
                'price' => $size->pivot?->price !== null ? (float) $size->pivot->price : null,
                'discount' => $size->pivot?->discount !== null ? (float) $size->pivot->discount : null,
                'stock' => $size->pivot?->stock !== null ? (int) $size->pivot->stock : null,
            ])),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant) => [
                'color_id' => (int) $variant->color_id,
                'size_id' => (int) $variant->size_id,
                'price' => $variant->price !== null ? (float) $variant->price : null,
                'discount' => $variant->discount !== null ? (float) $variant->discount : null,
                'stock' => $variant->stock !== null ? (int) $variant->stock : null,
            ])->values()),
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
                'slug' => $this->vendor->slug,
            ] : null),
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'is_promoted' => (bool) $this->is_promoted,
            'is_vip' => (bool) $this->is_vip,
            'is_premium' => (bool) $this->is_premium,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'views' => (int) $this->views,
            'contact_reveals' => (int) $this->contact_reveals,
            'is_active' => (bool) $this->is_active,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => $image->image_path)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
