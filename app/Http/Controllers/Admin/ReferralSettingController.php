<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\User\Entities\ReferralSetting;

class ReferralSettingController extends AdminController
{
    protected string $title = 'Referal';

    public function index()
    {
        $referred = \Modules\User\Entities\UserReferral::query()
            ->with(['user:id,name,phone', 'referredUsers.user:id,name,phone'])
            ->latest('id')
            ->limit(50)
            ->get();

        return view('admin.pages.referral', [
            'title' => $this->title,
            'setting' => ReferralSetting::query()->first() ?? new ReferralSetting(),
            'referrals' => $referred,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'referral_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $setting = ReferralSetting::query()->firstOrNew();
        $setting->fill($data)->save();

        return back()->with('status', __('Referal parametrləri yeniləndi.'));
    }
}
