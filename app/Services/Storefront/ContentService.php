<?php

namespace App\Services\Storefront;

use App\Models\ContactMessage;
use App\Services\Notification\AdminNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\HelpAndPolicy\Services\FaqService;
use Modules\HelpAndPolicy\Services\LegalTermsService;
use Modules\Product\Services\ReviewService;
use Modules\Setting\Entities\Setting;

/**
 * Read model for the public content pages (about, contact, FAQ, legal).
 * Everything comes from the module services the API already exposes, so the
 * two frontends cannot drift apart.
 */
class ContentService
{
    public function __construct(
        private readonly FaqService $faqs,
        private readonly LegalTermsService $terms,
        private readonly ReviewService $reviews,
    ) {}

    /**
     * The HTML body of a legal term (`about`, `privacy`, `terms`, …).
     */
    public function legalTerm(string $type): ?string
    {
        $payload = $this->terms->getAll(Request::create('/api/legal-terms', 'GET', ['type' => $type]))
            ->getData(true);

        $term = $payload['data'][0] ?? null;

        return $term ? $this->localize($term['html'] ?? null) : null;
    }

    /**
     * @return array<int, array{id:int, title:string, description:string, type:?string}>
     */
    public function faqs(): array
    {
        $payload = $this->faqs->getAll(Request::create('/api/faq', 'GET'))->getData(true);

        return collect($payload['data'] ?? [])
            ->map(fn ($faq) => [
                'id' => (int) ($faq['id'] ?? 0),
                'title' => $this->localize($faq['title'] ?? null),
                'description' => $this->localize($faq['description'] ?? null),
                'type' => $faq['type'] ?? null,
            ])
            ->filter(fn ($faq) => $faq['title'] !== '')
            ->values()
            ->all();
    }

    /**
     * Admin-picked testimonials shown on the home and about pages.
     */
    public function featuredReviews(int $limit = 6): array
    {
        $payload = $this->reviews->featured()->getData(true);

        return array_slice($payload['data'] ?? [], 0, $limit);
    }

    /**
     * Contact details from the store settings, same shape as GET /api/contact.
     */
    public function contactInfo(): array
    {
        return Cache::remember('storefront:contact_info', 300, function () {
            $setting = Setting::query()->first();

            $phones = [];

            if ($setting) {
                foreach (['phone_number_1', 'phone_number_2', 'phone_number_3', 'phone_number_4'] as $column) {
                    if (! empty($setting->{$column})) {
                        $phones[] = $setting->{$column};
                    }
                }
            }

            return [
                'address' => $setting?->address ?? 'Bakı, Azərbaycan',
                'email' => $setting?->email ?? 'support@snaker.store',
                'phone' => $setting?->phone_number_1 ?? ($phones[0] ?? null),
                'phones' => $phones,
                'whatsapp_number' => $setting?->whatsapp_number ?? null,
                'google_map_url' => $setting?->google_map_url ?? null,
                'instagram_url' => $setting?->instagram_url ?? null,
                'facebook_url' => $setting?->facebook_url ?? null,
                'twitter_url' => $setting?->twitter_url ?? null,
                'youtube_url' => $setting?->youtube_url ?? null,
                'telegram_url' => $setting?->telegram_url ?? null,
                'linkedin_url' => $setting?->linkedin_url ?? null,
                'tiktok_url' => $setting?->tiktok_url ?? null,
            ];
        });
    }

    /**
     * Store a public contact message and notify the admins, mirroring
     * App\Http\Controllers\Api\ContactController::send().
     */
    public function submitContact(array $data, ?string $ip): ContactMessage
    {
        $message = ContactMessage::create($data + ['ip_address' => $ip]);

        $subject = $message->subject ? ": {$message->subject}" : '';

        app(AdminNotifier::class)->notify(
            'Yeni əlaqə müraciəti',
            "{$message->full_name} tərəfindən yeni müraciət daxil oldu{$subject}.",
            ['type' => 'contact_message', 'id' => $message->id]
        );

        return $message;
    }

    /**
     * Translatable values arrive either as a string or as a locale map.
     */
    private function localize(mixed $value, string $locale = 'az'): string
    {
        if (is_array($value)) {
            return (string) ($value[$locale] ?? reset($value) ?: '');
        }

        return (string) ($value ?? '');
    }
}
