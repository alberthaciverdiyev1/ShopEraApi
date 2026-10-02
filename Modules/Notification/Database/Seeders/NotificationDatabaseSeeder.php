<?php

namespace Modules\Notification\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notification\Entities\Notification;
use Modules\User\Entities\User;

class NotificationDatabaseSeeder extends Seeder
{
    /**
     * Broadcast announcements plus a few order notifications addressed to the
     * customer they actually concern.
     */
    public function run(): void
    {
        $broadcasts = [
            [
                'title' => 'Yeni il endirimləri başladı!',
                'body' => 'Seçilmiş məhsullarda 30%-dək endirim. Kampaniya 31 yanvara qədər davam edir.',
                'data' => ['type' => 'campaign', 'deeplink' => '/campaign/new-year'],
            ],
            [
                'title' => 'Pulsuz çatdırılma',
                'body' => '50 AZN-dən yuxarı sifarişlərə Bakı daxilində çatdırılma pulsuzdur.',
                'data' => ['type' => 'campaign', 'deeplink' => '/delivery'],
            ],
            [
                'title' => 'Tətbiqi yeniləyin',
                'body' => 'Daha sürətli axtarış və yeni filtr imkanları üçün tətbiqin son versiyasını yükləyin.',
                'data' => ['type' => 'app_update'],
            ],
        ];

        foreach ($broadcasts as $item) {
            Notification::updateOrCreate(
                ['title' => $item['title'], 'all' => true],
                [
                    'body' => $item['body'],
                    'data' => $item['data'],
                    'source' => 'system',
                    'user_id' => null,
                    'icon' => 'https://logo.clearbit.com/snaker.store?size=128',
                    'image' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1200&q=80',
                    'url' => $item['data']['deeplink'] ?? null,
                ]
            );
        }

        $customer = User::query()->where('email', 'elvin.mammadov@gmail.com')->first();

        if ($customer) {
            $personal = [
                ['Sifarişiniz qəbul edildi', 'Sifarişiniz uğurla qeydə alındı. Tezliklə hazırlanmağa başlanacaq.', ['type' => 'order', 'status' => 'placed']],
                ['Sifarişiniz yoldadır', 'Kuryer sifarişinizi ünvanınıza çatdırır.', ['type' => 'order', 'status' => 'processing']],
                ['Sifarişiniz çatdırıldı', 'Sifarişiniz çatdırıldı. Məhsulları qiymətləndirməyi unutmayın!', ['type' => 'order', 'status' => 'delivered']],
            ];

            foreach ($personal as $item) {
                Notification::updateOrCreate(
                    ['title' => $item[0], 'user_id' => $customer->id],
                    [
                        'body' => $item[1],
                        'data' => $item[2],
                        'source' => 'system',
                        'all' => false,
                        'icon' => 'https://logo.clearbit.com/snaker.store?size=128',
                    ]
                );
            }
        }
    }
}
