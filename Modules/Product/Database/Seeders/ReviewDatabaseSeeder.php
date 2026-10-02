<?php

namespace Modules\Product\Database\Seeders;

use App\Enums\ReviewStatus;
use Illuminate\Database\Seeder;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Review;
use Modules\User\Entities\User;

class ReviewDatabaseSeeder extends Seeder
{
    /**
     * Written customer reviews, tied to real products and customer accounts.
     * Run after the product catalogue so product ids exist.
     */
    public function run(): void
    {
        $products = Product::query()->pluck('id')->all();
        $customers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'user'))
            ->pluck('id')
            ->all();

        if (! $products || ! $customers) {
            return;
        }

        // Rebuild the review set so repeated seeding does not accumulate rows.
        Review::query()->delete();

        $comments = [
            5 => [
                'Məhsul tam gözlədiyim kimi gəldi, keyfiyyəti əladır.',
                'Çox sürətli çatdırıldı, qablaşdırma da səliqəli idi. Təşəkkürlər!',
                'Qiymətinə görə çox yaxşı seçimdir, tövsiyə edirəm.',
                'İkinci dəfədir alıram, keyfiyyəti dəyişmir. Məmnunam.',
                'Rəsmi zəmanətlə gəldi, mağazaya etimad tamdır.',
                'Göründüyü kimi, hətta daha yaxşıdır. Razıyam.',
            ],
            4 => [
                'Məhsul yaxşıdır, sadəcə çatdırılma bir gün gecikdi.',
                'Keyfiyyəti yaxşıdır, qiyməti bir az yüksəkdir.',
                'Ümumilikdə məmnunam, kiçik qüsurlar var idi.',
                'Gözlədiyim kimi, amma təsvirdə bir az fərq var.',
                'Yaxşı məhsuldur, yenidən ala bilərəm.',
            ],
            3 => [
                'Orta səviyyəli məhsuldur, qiyməti ilə uyğundur.',
                'İşləyir, amma keyfiyyət daha yaxşı ola bilərdi.',
                'Elə də pis deyil, amma gözləntimi tam qarşılamadı.',
            ],
            2 => [
                'Gözlədiyim keyfiyyətdə deyildi, geri qaytarmağı düşünürəm.',
                'Çatdırılma gecikdi, məhsul da zədəli gəldi.',
            ],
            1 => [
                'Təəssüf ki, məhsul təsvirə uyğun deyildi.',
            ],
        ];

        // Deterministic assignment so repeated seeding stays consistent.
        mt_srand(20260930);

        $reviewIndex = 0;

        foreach ($products as $productId) {
            $count = mt_rand(1, 4);
            $usedUsers = [];

            for ($i = 0; $i < $count; $i++) {
                $userId = $customers[mt_rand(0, count($customers) - 1)];

                if (in_array($userId, $usedUsers, true)) {
                    continue;
                }
                $usedUsers[] = $userId;

                $rate = match (true) {
                    $reviewIndex % 10 === 7 => 3,
                    $reviewIndex % 13 === 9 => 2,
                    $reviewIndex % 17 === 11 => 1,
                    $reviewIndex % 3 === 0 => 5,
                    default => mt_rand(4, 5),
                };
                $rate = max(1, min(5, $rate));

                $pool = $comments[$rate];
                $comment = $pool[mt_rand(0, count($pool) - 1)];

                Review::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'rate' => $rate,
                    'comment' => $comment,
                    'status' => $reviewIndex % 9 === 4 ? ReviewStatus::PENDING->value : ReviewStatus::APPROVED->value,
                    'created_at' => now()->subDays(mt_rand(1, 120)),
                    'updated_at' => now()->subDays(mt_rand(0, 30)),
                ]);

                $reviewIndex++;
            }
        }
    }
}
