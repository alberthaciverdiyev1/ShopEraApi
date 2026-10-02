<?php

namespace Modules\User\Services;

use Modules\User\Entities\ReferralSetting;

class ReferralSettingService
{
    private ReferralSetting $setting;

    public function __construct(ReferralSetting $setting)
    {
        $this->setting = $setting;
    }

    public function list()
    {
        $data = $this->setting->first();

        return responseHelper(__('Referral settings retrieved successfully.'), 200, $data);
    }

    public function update($request)
    {
        $validated = $request->validate([
            'referral_amount' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $setting = $this->setting->first();

        if (! $setting) {
            return responseHelper(__('Referral setting not found.'), 404);
        }

        $setting->update([
            'referral_amount' => $validated['referral_amount'],
            'is_active' => $validated['is_active'],
        ]);

        return responseHelper(__('Referral settings updated successfully.'),
            200,
            $setting
        );
    }

    public function checkAndAmount(): array
    {
        $setting = $this->setting->first();

        return [
            'is_active' => (bool) ($setting->is_active ?? false),
            'amount' => (float) ($setting->referral_amount ?? 0),
        ];
    }

    /** Admin settings page payload: current setting + latest referrals. */
    public function adminView(): array
    {
        return [
            'setting' => $this->setting->newQuery()->first() ?? new \Modules\User\Entities\ReferralSetting(),
            'referrals' => \Modules\User\Entities\UserReferral::query()
                ->with(['user:id,name,phone', 'referredUsers.user:id,name,phone'])
                ->latest('id')
                ->limit(50)
                ->get(),
        ];
    }

    public function save(array $data): void
    {
        $setting = $this->setting->newQuery()->firstOrNew();
        $setting->fill($data)->save();
    }
}
