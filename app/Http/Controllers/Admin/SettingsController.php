<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Entities\Setting;

class SettingsController extends AdminController
{
    protected string $title = 'Parametrlər';

    /**
     * Grouped edit schema. Each entry: [field, label, type, column-width(12)].
     * "translatable_textarea" stores a per-locale map.
     */
    private const GROUPS = [
        'Əlaqə və sosial şəbəkələr' => [
            'icon' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z',
            'fields' => [
                ['instagram_url', 'Instagram URL', 'text', 6],
                ['facebook_url', 'Facebook URL', 'text', 6],
                ['twitter_url', 'X / Twitter URL', 'text', 6],
                ['youtube_url', 'YouTube URL', 'text', 6],
                ['telegram_url', 'Telegram URL', 'text', 6],
                ['linkedin_url', 'LinkedIn URL', 'text', 6],
                ['tiktok_url', 'TikTok URL', 'text', 6],
                ['whatsapp_number', 'WhatsApp nömrə', 'text', 6],
                ['email', 'Email', 'text', 6],
                ['facebook_url', 'Facebook URL', 'text', 6],
                ['twitter_url', 'X (Twitter) URL', 'text', 6],
                ['telegram_url', 'Telegram URL', 'text', 6],
                ['youtube_url', 'YouTube URL', 'text', 6],
                ['linkedin_url', 'LinkedIn URL', 'text', 6],
                ['google_map_url', 'Google Maps URL', 'text', 6],
                ['phone_number_1', 'Telefon 1', 'text', 3],
                ['phone_number_2', 'Telefon 2', 'text', 3],
                ['phone_number_3', 'Telefon 3', 'text', 3],
                ['phone_number_4', 'Telefon 4', 'text', 3],
                ['address', 'Ünvan', 'text', 12],
            ],
        ],
        'Tətbiq və limitlər' => [
            'icon' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
            'fields' => [
                ['minimal_purchase_price', 'Minimal sifariş məbləği', 'number', 6],
                ['referral_reward_amount', 'Referal mükafatı', 'number', 6],
                ['public_low_stock_threshold', 'Az qalıb həddi', 'number', 6],
            ],
        ],
    ];

    public function index()
    {
        $setting = Setting::query()->first() ?? new Setting;

        return view('admin.pages.settings', [
            'title' => $this->title,
            'setting' => $setting,
            'groups' => self::GROUPS,
            'locales' => ['az', 'en', 'ru', 'tr'],
        ]);
    }

    public function update(Request $request)
    {
        $this->requirePermission('update setting');

        $setting = Setting::query()->firstOrFail();
        $data = [];

        foreach (self::GROUPS as $group) {
            foreach ($group['fields'] as [$name, , $type]) {
                if ($type === 'translatable_textarea') {
                    $value = $request->input($name);
                    $value = is_array($value) ? array_filter($value, fn ($v) => $v !== null && $v !== '') : [];
                    $data[$name] = $value ?: null;

                    continue;
                }

                if ($request->has($name)) {
                    $data[$name] = $request->input($name);
                }
            }
        }

        $setting->update($data);
        Cache::forget(TenantContext::cacheKey('settings_list'));
        Cache::forget(TenantContext::cacheKey('settings_first'));
        Cache::forget(TenantContext::cacheKey('storefront:settings'));

        return back()->with('status', __('Parametrlər yeniləndi.'));
    }
}
