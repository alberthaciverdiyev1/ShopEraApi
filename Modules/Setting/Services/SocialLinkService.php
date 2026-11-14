<?php

namespace Modules\Setting\Services;

use App\Core\Support\TenantContext;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Entities\SocialLink;

class SocialLinkService
{
    /**
     * Supported networks: the label shown in the admin and the Font Awesome
     * brand class the footer renders.
     */
    public const PLATFORMS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'telegram' => 'Telegram',
        'whatsapp' => 'WhatsApp',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'x' => 'X (Twitter)',
        'linkedin' => 'LinkedIn',
    ];

    private const ICONS = [
        'facebook' => 'fa-brands fa-facebook-f',
        'instagram' => 'fa-brands fa-instagram',
        'telegram' => 'fa-brands fa-telegram',
        'whatsapp' => 'fa-brands fa-whatsapp',
        'youtube' => 'fa-brands fa-youtube',
        'tiktok' => 'fa-brands fa-tiktok',
        'x' => 'fa-brands fa-x-twitter',
        'linkedin' => 'fa-brands fa-linkedin-in',
    ];

    private const CACHE_KEY = 'storefront:social-links';

    public function __construct(private readonly SocialLink $model) {}

    /** Active links in display order, ready for the footer. */
    public function links(): array
    {
        $rows = Cache::remember(TenantContext::cacheKey(self::CACHE_KEY), 600, function () {
            return $this->model->newQuery()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['platform', 'url'])
                ->map(fn (SocialLink $link) => [
                    'platform' => $link->platform,
                    'url' => $link->url,
                ])
                ->all();
        });

        return array_values(array_map(fn (array $row) => [
            'platform' => $row['platform'],
            'label' => self::PLATFORMS[$row['platform']] ?? ucfirst((string) $row['platform']),
            'icon' => self::ICONS[$row['platform']] ?? 'fa-solid fa-link',
            'url' => (string) $row['url'],
        ], $rows));
    }

    public static function forgetCache(): void
    {
        Cache::forget(TenantContext::cacheKey(self::CACHE_KEY));
    }
}
