<?php

namespace Modules\Setting\Services;

use App\Core\Support\TenantContext;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Entities\FooterLink;

class FooterLinkService
{
    /** Footer columns, in the order they are rendered. */
    public const SECTIONS = [
        'catalog' => 'Kataloq',
        'information' => 'İnformasiya',
        'contact' => 'Əlaqə',
    ];

    private const CACHE_KEY = 'storefront:footer-links';

    public function __construct(private readonly FooterLink $model) {}

    /**
     * Active links grouped by column: `['catalog' => [['label' => ..., 'url' => ...]]]`.
     */
    public function groups(): array
    {
        $rows = Cache::remember(TenantContext::cacheKey(self::CACHE_KEY), 600, function () {
            return $this->model->newQuery()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['section', 'label', 'url'])
                ->map(fn (FooterLink $link) => [
                    'section' => $link->section,
                    'label' => $link->getTranslations('label'),
                    'url' => $link->url,
                ])
                ->all();
        });

        $locale = app()->getLocale();
        $groups = [];

        foreach ($rows as $row) {
            $label = $row['label'][$locale] ?? $row['label']['az'] ?? reset($row['label']);

            if ($label === false || $label === '') {
                continue;
            }

            $groups[$row['section']][] = [
                'label' => (string) $label,
                'url' => (string) $row['url'],
            ];
        }

        return $groups;
    }

    public static function forgetCache(): void
    {
        Cache::forget(TenantContext::cacheKey(self::CACHE_KEY));
    }
}
