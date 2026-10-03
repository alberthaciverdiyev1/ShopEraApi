<?php

namespace App\Http\Controllers\Admin;

use App\Support\Subscription;
use Modules\Manager\Entities\Plan;
use Modules\Manager\Entities\Setting;
use Modules\Setting\Entities\Setting as TenantSetting;

/**
 * "Upgrade plan" page shown when an admin clicks a locked (Premium) sidebar
 * item: current plan, the available plans/features, and a WhatsApp CTA.
 */
class PlanController extends AdminController
{
    protected string $title = 'Planı yüksəlt';

    public function index()
    {
        $plans = [];
        $whatsapp = null;
        $supportEmail = null;

        try {
            $plans = Plan::query()->with('features')->orderBy('sort_order')->get();
            $whatsapp = Setting::query()->where('key', 'support_whatsapp')->value('value');
            $supportEmail = Setting::query()->where('key', 'support_email')->value('value');
        } catch (\Throwable) {
            // Control DB unreachable — still render the page with the CTA.
        }

        if (! $whatsapp) {
            try {
                $whatsapp = TenantSetting::query()->value('whatsapp_number');
            } catch (\Throwable) {
                // ignore
            }
        }

        return view('admin.pages.plan', [
            'title' => $this->title,
            'currentPlan' => Subscription::plan(),
            'plans' => $plans,
            'whatsapp' => $whatsapp,
            'supportEmail' => $supportEmail,
        ]);
    }
}
