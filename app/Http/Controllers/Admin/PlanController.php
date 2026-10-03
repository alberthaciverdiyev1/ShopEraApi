<?php

namespace App\Http\Controllers\Admin;

use App\Support\Features;
use App\Support\Subscription;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Modules\Category\Entities\Category;
use Modules\Manager\Entities\Plan;
use Modules\Manager\Entities\Setting;
use Modules\Order\Entities\Order;
use Modules\Product\Entities\Product;
use Modules\Setting\Entities\Setting as TenantSetting;
use Modules\User\Entities\User;

/**
 * Plan page: the current plan with its limits/usage, the available plans and
 * a WhatsApp CTA. Shown both from the sidebar and from locked (Premium) items.
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

        $usage = [
            'max_products' => $this->safe(fn () => Product::query()->count()),
            'max_categories' => $this->safe(fn () => Category::query()->count()),
            'max_staff' => $this->safe(fn () => User::query()->whereHas('roles', fn ($q) => $q->where('name', '!=', 'user'))->count()),
            'max_orders' => $this->safe(fn () => Order::query()->count()),
            'storage_gb' => $this->storageGb(),
        ];

        $labels = [
            'max_products' => 'Məhsullar',
            'max_categories' => 'Kateqoriyalar',
            'max_staff' => 'İşçilər',
            'max_orders' => 'Sifarişlər',
            'storage_gb' => 'Yaddaş (GB)',
        ];

        $rows = [];
        foreach ($labels as $key => $label) {
            $rows[] = [
                'label' => $label,
                'used' => $usage[$key] ?? 0,
                'limit' => $limits[$key] ?? null,
            ];
        }

        return $rows;
    }

    private function storageGb(): float
    {
        try {
            $prefix = TenantContext::storagePath('');
            $bytes = 0;
            foreach (Storage::disk('public')->allFiles($prefix) as $file) {
                $bytes += (int) Storage::disk('public')->size($file);
            }

            return round($bytes / 1073741824, 2);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function safe(callable $fn): int
    {
        try {
            return (int) $fn();
        } catch (\Throwable) {
            return 0;
        }
    }
}
