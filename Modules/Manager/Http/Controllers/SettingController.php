<?php

namespace Modules\Manager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Manager\Entities\Setting;

/**
 * Global SaaS settings (support contact etc.) stored in the control DB and
 * shown to tenant admins on the plan/upgrade page.
 */
class SettingController extends Controller
{
    private const KEYS = [
        'support_whatsapp' => 'Dəstək WhatsApp nömrəsi',
        'support_email' => 'Dəstək e-poçtu',
        'admin_disclaimer' => 'Admin panel disclaimer',
    ];

    public function index()
    {
        $values = Setting::query()
            ->whereIn('key', array_keys(self::KEYS))
            ->pluck('value', 'key')
            ->all();

        return view('manager::settings.index', [
            'title' => 'Parametrlər',
            'keys' => self::KEYS,
            'values' => $values,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'support_whatsapp' => ['nullable', 'string', 'max:32'],
            'support_email' => ['nullable', 'email', 'max:190'],
            'admin_disclaimer' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach (self::KEYS as $key => $label) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $data[$key] ?? null, 'label' => $label, 'group' => $key === 'admin_disclaimer' ? 'admin' : 'support']
            );
        }

        return back()->with('status', __('Parametrlər yeniləndi.'));
    }
}
