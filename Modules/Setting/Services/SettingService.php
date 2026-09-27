<?php

namespace Modules\Setting\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Http\Entities\Setting;
use Modules\Setting\Http\Resources\SettingResource;

class SettingService
{
    private Setting $model;

    public function __construct(Setting $model)
    {
        $this->model = $model;
    }

    /**
     * Get settings list
     */
    public function list(): JsonResponse
    {
        $cacheKey = 'settings_list';

        $data = Cache::remember(
            $cacheKey,
            config('cache.setting_list_cache_time', 3600),
            fn () => $this->model->all()
        );

        return responseHelper(__('Settings retrieved successfully.'), 200, SettingResource::collection($data));
    }

    /**
     * Update setting
     */
    public function update($request): JsonResponse
    {
        $validated = $request->validated();

        $setting = handleTransaction(
            function () use ($validated) {
                $setting = $this->model->first();
                $setting->update($validated);
                return $setting->refresh();
            },
            'Setting updated successfully.',
            SettingResource::class
        );

        Cache::forget('settings_list');
        Cache::forget('settings_first');

        return $setting;
    }

    public function getSettingFieldData(string $field)
    {
        $setting = $this->firstSetting();
        return $setting?->{$field};
    }

    public function getMinimalPurchasePriceForUser($user = null): float
    {
        return $this->getMinimalPurchasePrice();
    }

    public function getMinimalPurchasePrice(): float
    {
        $setting = $this->firstSetting();

        return (float)($setting?->minimal_purchase_price ?? 15);
    }

    public function getWholesaleMinimalPurchasePrice(): float
    {
        $setting = $this->firstSetting();

        return (float)($setting?->wholesale_minimal_purchase_price ?? 100);
    }

    public function getStoreCommissionPercent(): float
    {
        return (float) ($this->firstSetting()?->store_commission_percent ?? 10);
    }

    public function getStoreNegativeBalanceLimit(): float
    {
        return (float) ($this->firstSetting()?->store_negative_balance_limit ?? 10);
    }

    public function getStoreHandoverHours(): int
    {
        return (int) ($this->firstSetting()?->store_handover_hours ?? 24);
    }

    public function getStoreLatePenaltyAmount(): float
    {
        return (float) ($this->firstSetting()?->store_late_penalty_amount ?? 0);
    }

    public function getStoreMinWithdrawalAmount(): float
    {
        return (float) ($this->firstSetting()?->store_min_withdrawal_amount ?? 0);
    }

    public function getStoreReleaseHoldHours(): int
    {
        return (int) ($this->firstSetting()?->store_release_hold_hours ?? 0);
    }

    public function isMarketplaceEnabled(): bool
    {
        $value = $this->firstSetting()?->marketplace_enabled;

        return $value === null ? true : (bool) $value;
    }

    public function getPublicLowStockThreshold(): int
    {
        return (int) ($this->firstSetting()?->public_low_stock_threshold ?? 20);
    }

    private function firstSetting(): ?Setting
    {
        return Cache::remember(
            'settings_first',
            config('cache.setting_list_cache_time', 3600),
            fn () => $this->model->first()
        );
    }

    public function changeLocale($request)
    {
        $request->validate([
            'locale' => ['required', 'in:az,en,ru,tr'],
        ]);

        return responseHelper(__('Locale changed successfully.'), 200, [
            'locale' => $request->locale,
        ]);
    }
}
