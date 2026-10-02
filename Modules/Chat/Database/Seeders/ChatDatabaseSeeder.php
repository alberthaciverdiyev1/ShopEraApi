<?php

namespace Modules\Chat\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Chat\Entities\Conversation;
use Modules\Chat\Entities\Message;
use Modules\User\Entities\User;

class ChatDatabaseSeeder extends Seeder
{
    /**
     * Realistic support conversations between customers and a store admin.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'alberthaciverdiyev55@gmail.com')->first();
        $customers = User::query()
            ->whereIn('email', [
                'elvin.mammadov@gmail.com',
                'aysel.aliyeva@gmail.com',
                'rashad.huseynov@gmail.com',
            ])->get();

        if (! $admin || $customers->isEmpty()) {
            return;
        }

        $threads = [
            'elvin.mammadov@gmail.com' => [
                ['user', 'Salam, sifarişim nə vaxt çatdırılacaq?'],
                ['admin', 'Salam! Sifarişiniz hazırlanır, Bakı daxilində 1-2 iş günü ərzində çatdırılacaq.'],
                ['user', 'Çox sağ olun, gözləyirəm.'],
            ],
            'aysel.aliyeva@gmail.com' => [
                ['user', 'Məhsulu geri qaytarmaq istəyirəm, mümkündürmü?'],
                ['admin', 'Salam! Bəli, 14 gün ərzində məhsulu geri qaytara bilərsiniz. Sifariş nömrənizi göndərə bilərsiniz?'],
                ['user', '#1042 nömrəli sifariş.'],
                ['admin', 'Təşəkkür edirik, kuryer geri qaytarma üçün sizinlə əlaqə saxlayacaq.'],
            ],
            'rashad.huseynov@gmail.com' => [
                ['user', 'Bu məhsulun zəmanəti varmı?'],
                ['admin', 'Bəli, bütün elektronika məhsullarına 1 il rəsmi zəmanət verilir.'],
            ],
        ];

        foreach ($threads as $email => $messages) {
            $customer = $customers->firstWhere('email', $email);
            if (! $customer) {
                continue;
            }

            $conversation = Conversation::updateOrCreate(
                ['user_id' => $customer->id, 'admin_id' => $admin->id],
                ['last_message_at' => now()]
            );

            foreach ($messages as $index => [$sender, $text]) {
                Message::updateOrCreate(
                    ['conversation_id' => $conversation->id, 'message' => $text],
                    [
                        'sender_type' => $sender,
                        'sender_id' => $sender === 'admin' ? $admin->id : $customer->id,
                        'is_read' => true,
                        'created_at' => now()->subMinutes((count($messages) - $index) * 7),
                        'updated_at' => now()->subMinutes((count($messages) - $index) * 7),
                    ]
                );
            }
        }
    }
}
