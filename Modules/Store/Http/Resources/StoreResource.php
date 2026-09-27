<?php

namespace Modules\Store\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Setting\Services\SettingService;
use Modules\Store\Support\StoreLabels;

class StoreResource extends JsonResource
{
    public function toArray($request): array
    {
        $commission = $this->commission_percent_override
            ?? app(SettingService::class)->getStoreCommissionPercent();
        $negativeLimit = $this->negative_balance_limit_override
            ?? app(SettingService::class)->getStoreNegativeBalanceLimit();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'owner_full_name' => $this->owner_full_name,
            'name' => $this->name,
            'about' => $this->about,
            'logo_path' => $this->logo_path,
            'status' => $this->status,
            'status_label' => StoreLabels::storeStatus($this->status),
            'is_active' => (bool) $this->is_active,
            'is_trusted' => (bool) $this->is_trusted,
            'balance' => (float) $this->balance,
            'commission_percent' => (float) $commission,
            'negative_balance_limit' => (float) $negativeLimit,
            'deactivated_reason' => $this->deactivated_reason,
            'deactivated_reason_label' => StoreLabels::deactivatedReason($this->deactivated_reason),
            'rejection_reason' => $this->rejection_reason,
            'changes_requested_reason' => $this->changes_requested_reason,
            'suspension_reason' => $this->suspension_reason,
            'pending_balance' => round(app(\Modules\Store\Services\StoreBalanceService::class)->pending($this->resource), 2),
            'withdrawable_balance' => app(\Modules\Store\Services\StoreBalanceService::class)->withdrawable($this->resource),
            'instructions_accepted_at' => $this->instructions_accepted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'identity_documents' => [
                'front' => route('api.store.identity-document', ['side' => 'front']),
                'back' => route('api.store.identity-document', ['side' => 'back']),
            ],
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'surname' => $this->user->surname,
                'phone' => $this->user->phone,
                'email' => $this->user->email,
            ]),
        ];
    }
}
