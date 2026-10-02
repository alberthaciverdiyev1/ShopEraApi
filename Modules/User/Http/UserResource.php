<?php

namespace Modules\User\Http;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Modules\Setting\Services\SettingService;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $settingsService = app(SettingService::class);
        $minimalPurchasePrice = $settingsService->getMinimalPurchasePriceForUser($this->resource);

        return [
            'id' => $this->id,
            'name' => Str::title($this->name),
            'surname' => Str::title($this->surname),
            'avatar' => $this->avatar,
            'is_active' => $this->is_active,
            'is_wholesaler' => (bool) $this->is_wholesaler,
            'effective_minimal_purchase_price' => $minimalPurchasePrice,
            'wholesale_minimal_purchase_price' => $settingsService->getWholesaleMinimalPurchasePrice(),
            'email' => $this->email,
            'phone' => $this->phone,
            'total_balance' => $this->total_balance,
            'referral_code' => $this->referral_code,
            'created_at' => $this->created_at,
            // Yalnız rollar onsuz da yüklənəndə: admin panelin komanda
            // siyahısı kimin admin, kimin manager olduğunu göstərsin.
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
        ];
    }
}
