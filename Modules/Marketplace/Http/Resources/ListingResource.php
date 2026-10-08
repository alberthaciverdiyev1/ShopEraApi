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
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'seller_type' => $this->seller_type,
            'condition' => $this->condition,
            'category_id' => $this->category_id,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => $this->city?->name),
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
                'slug' => $this->vendor->slug,
            ] : null),
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'is_promoted' => (bool) $this->is_promoted,
            'is_premium' => (bool) $this->is_premium,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => $image->image_path)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
