<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Services\Notification\AdminNotifier;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Entities\Setting;

class ContactController extends Controller
{
    /**
     * Get store contact and social media information.
     */
    public function info(): JsonResponse
    {
        $setting = Setting::query()->first();

        $phones = [];
        if ($setting) {
            foreach (['phone_number_1', 'phone_number_2', 'phone_number_3', 'phone_number_4'] as $col) {
                if (! empty($setting->{$col})) {
                    $phones[] = $setting->{$col};
                }
            }
        }

        $data = [
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

        return responseHelper('Contact information retrieved successfully.', 200, $data);
    }

    /**
     * Submit a contact message.
     */
    public function send(ContactRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['ip_address'] = $request->ip();

        $contactMessage = ContactMessage::create($validated);

        // Notify admins about the new message
        $sender = $contactMessage->full_name;
        $subject = $contactMessage->subject ? ": {$contactMessage->subject}" : '';
        app(AdminNotifier::class)->notify(
            'Yeni əlaqə müraciəti',
            "{$sender} tərəfindən yeni müraciət daxil oldu{$subject}.",
            [
                'type' => 'contact_message',
                'id' => $contactMessage->id,
            ]
        );

        return responseHelper('Mesajınız uğurla göndərildi. Tezliklə sizinlə əlaqə saxlanılacaq.', 200, [
            'id' => $contactMessage->id,
        ]);
    }
}
