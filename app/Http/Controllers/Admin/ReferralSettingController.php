<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\User\Services\ReferralSettingService;

class ReferralSettingController extends AdminController
{
    protected string $title = 'Referal';

    public function index()
    {
        return view('admin.pages.referral', array_merge(
            ['title' => $this->title],
            app(ReferralSettingService::class)->adminView()
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'referral_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        app(ReferralSettingService::class)->save($data);

        return back()->with('status', __('Referal parametrləri yeniləndi.'));
    }
}
