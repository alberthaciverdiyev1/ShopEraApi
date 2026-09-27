<?php

namespace Modules\Store\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Store\Support\StoreLabels;

class StoreWalletTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'type' => $this->type,
            'type_label' => StoreLabels::walletType($this->type),
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'note' => $this->note,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
