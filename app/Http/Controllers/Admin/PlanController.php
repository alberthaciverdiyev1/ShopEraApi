<?php

namespace App\Http\Controllers\Admin;

use App\Support\Features;
use App\Support\PlanUsage;
use App\Support\Subscription;
use Modules\Manager\Entities\Plan;
use Modules\Manager\Entities\Setting;
use Modules\Setting\Entities\Setting as TenantSetting;

/**
 * Plan page: the current plan with its limits/usage (products, storage),
 * the available plans and a WhatsApp CTA. Shown from the sidebar and from
 * locked (Premium) items.
 */
class PlanController extends AdminController
{
    protected string $title = 'Plan və limitlər';

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
            'limits' => $this->limits(),
            'plans' => $plans,
            'whatsapp' => $whatsapp,
            'supportEmail' => $supportEmail,
        ]);
    }

    /** Current plan limits paired with live usage: label, used, limit (null = unlimited). */
    private function limits(): array
    {
        $limits = Features::limits();

        $labels = [
            'max_products' => 'Məhsullar',
            'storage_mb' => 'Yaddaş (MB)',
        ];

        $rows = [];
        foreach ($labels as $key => $label) {
            $rows[] = [
                'label' => $label,
                'used' => PlanUsage::value($key),
                'limit' => $limits[$key] ?? null,
            ];
        }

        return $rows;
    }
}
