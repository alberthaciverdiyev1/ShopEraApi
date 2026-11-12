<?php

namespace Modules\Setting\Services;

use Modules\Notification\Services\AdminNotifier;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Entities\ContactMessage;
use Modules\Setting\Entities\Setting;

/**
 * Store contact details and public contact-form messages.
 */
class ContactService
{
    /**
     * Contact details from the store settings, same shape as GET /api/contact.
     */
    public function info(): array
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
     * Store a public contact message and notify the admins.
     */
    public function send(array $data, ?string $ip): ContactMessage
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
}
