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
                ['app_version', 'Tətbiq versiyası (Android)', 'text', 6],
                ['app_version_ios', 'Tətbiq versiyası (iOS)', 'text', 6],
                ['minimal_purchase_price', 'Minimal sifariş məbləği', 'number', 6],
                ['referral_reward_amount', 'Referal mükafatı', 'number', 6],
                ['public_low_stock_threshold', 'Az qalıb həddi', 'number', 6],
            ],
        ],
        'Mağaza / Marketplace' => [
            'icon' => 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z',
            'fields' => [
                ['store_commission_percent', 'Mağaza komissiyası (%)', 'number', 6],
                ['store_negative_balance_limit', 'Mənfi balans limiti', 'number', 6],
                ['store_handover_hours', 'Təhvil saatı', 'number', 6],
                ['store_late_penalty_amount', 'Gecikmə cəriməsi', 'number', 6],
            ],
        ],
        'Satıcı təlimatı' => [
            'icon' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
            'fields' => [
                ['seller_instructions', 'Satıcı təlimatı', 'translatable_textarea', 12],
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
