<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'rate' => $this->rate,
            'comment' => $this->comment,
            'image' => $this->image,
            'status_name' => $this->status ? $this->status->label() : null,
            'status_code' => $this->status ? $this->status->name : null,

            'product' => $this->product,
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
                'email' => $this->user->email ?? null,
            ],
            'product_id' => $this->product_id,
            'created_at' => $this->created_at?->format('d.m.Y H:i'),
        ];
    }
}
