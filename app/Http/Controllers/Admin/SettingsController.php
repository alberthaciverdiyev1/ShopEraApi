<?php

namespace App\Http\Controllers\Admin;

use App\Support\Features;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Services\SettingService;

class SettingsController extends AdminController
{
    protected string $title = 'Parametrlər';

    /**
     * Grouped edit schema. Each entry: [field, label, type, column-width(12)].
     * "translatable_textarea" stores a per-locale map, "password" is write-only
     * (blank keeps the stored value).
     */
    private const BASE_GROUPS = [
        'Brendinq' => [
            'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6z',
            'fields' => [
                ['logo', 'Logo', 'image', 6],
                ['favicon', 'Favicon', 'image', 6],
            ],
        ],

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

    /**
     * Full schema for the current tenant. The CJ Dropshipping group only exists
     * when the owner's `cj_dropshipping` entitlement is active, so the API key
     * field is neither shown nor persisted otherwise.
     *
     * @return array<string,array{icon:string,fields:array<int,array{0:string,1:string,2:string,3:int}>}>
     */
    private function groups(): array
    {
        $groups = self::BASE_GROUPS;

        // Strict default: the field appears only when the entitlement was
        // explicitly enabled for this owner (not on an unsynced instance).
        if (Features::enabled('cj_dropshipping', false)) {
            $groups['CJ Dropshipping'] = [
                'icon' => 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m12.75 0V6.75m0 2.599l-1.6-.64a9.06 9.06 0 00-6.55 0l-1.6.64m11.75 0a9.07 9.07 0 01-3.25 1.71M3.75 9.35a9.06 9.06 0 003.25 1.71m6 0a9.06 9.06 0 01-6 0',
                'fields' => [
                    ['cj_dropshipping_api_key', 'CJ API key', 'password', 12],
                ],
            ];
        }

        return $groups;
    }

    public function index()
    {
        $setting = app(SettingService::class)->current();

        return view('admin.pages.settings', [
            'title' => $this->title,
            'setting' => $setting,
            'groups' => $this->groups(),
            'locales' => $this->enabledLocales(),
        ]);
    }

    public function update(Request $request)
    {
        $this->requirePermission('update setting');

        $setting = app(SettingService::class)->currentOrFail();
        $data = [];

        foreach ($this->groups() as $group) {
            foreach ($group['fields'] as [$name, , $type]) {
                if ($type === 'translatable_textarea') {
                    $value = $request->input($name);
                    $value = is_array($value) ? array_filter($value, fn ($v) => $v !== null && $v !== '') : [];
                    $data[$name] = $value ?: null;

                    continue;
                }

                if ($type === 'image') {
                    if ($request->hasFile($name)) {
                        $data[$name] = $request->file($name)->store(TenantContext::storagePath('branding'), 'public');
                    } elseif ($request->boolean('remove_'.$name)) {
                        $data[$name] = null;
                    }

                    continue;
                }

                // Write-only secret: a blank field keeps the stored value and
                // the current value is never rendered back into the HTML.
                if ($type === 'password') {
                    $value = $request->input($name);

                    if (is_string($value) && $value !== '') {
                        $data[$name] = $value;
                    }

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
