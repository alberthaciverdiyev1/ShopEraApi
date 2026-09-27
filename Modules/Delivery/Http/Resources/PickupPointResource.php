<?php

namespace Modules\Delivery\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupPointResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int)$this->id,
            'starex_delivery_point_id' => $this->starex_delivery_point_id ? (int) $this->starex_delivery_point_id : null,
            'name' => (string)$this->name,
            'address' => (string)$this->address,
            'price' => (float)$this->price,
            'delivery_time'=>$this->delivery_time,
            'is_active' => (bool)$this->is_active,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
