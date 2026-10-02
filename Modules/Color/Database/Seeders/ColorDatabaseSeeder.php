<?php

namespace Modules\Color\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Color\Entities\Color;

class ColorDatabaseSeeder extends Seeder
{
    /**
     * Real, translatable colour names with their reference hex codes.
     *
     * The `name` column is a plain string holding JSON, so matching is done in
     * PHP rather than with a json query operator (which only exists on jsonb).
     */
    public function run(): void
    {
        $colors = [
            ['Black', 'Qara', 'Чёрный', 'Siyah', '#000000'],
            ['White', 'Ağ', 'Белый', 'Beyaz', '#FFFFFF'],
            ['Gray', 'Boz', 'Серый', 'Gri', '#808080'],
            ['Silver', 'Gümüşü', 'Серебристый', 'Gümüş', '#C0C0C0'],
            ['Red', 'Qırmızı', 'Красный', 'Kırmızı', '#E53935'],
            ['Blue', 'Mavi', 'Синий', 'Mavi', '#1E88E5'],
            ['Navy', 'Tünd mavi', 'Тёмно-синий', 'Lacivert', '#0D1B4C'],
            ['Green', 'Yaşıl', 'Зелёный', 'Yeşil', '#43A047'],
            ['Yellow', 'Sarı', 'Жёлтый', 'Sarı', '#FDD835'],
            ['Orange', 'Narıncı', 'Оранжевый', 'Turuncu', '#FB8C00'],
            ['Pink', 'Çəhrayı', 'Розовый', 'Pembe', '#EC407A'],
            ['Purple', 'Bənövşəyi', 'Фиолетовый', 'Mor', '#8E24AA'],
            ['Brown', 'Qəhvəyi', 'Коричневый', 'Kahverengi', '#6D4C41'],
            ['Beige', 'Bej', 'Бежевый', 'Bej', '#D7C8AF'],
            ['Gold', 'Qızılı', 'Золотой', 'Altın', '#D4AF37'],
            ['Teal', 'Firuzəyi', 'Бирюзовый', 'Turkuaz', '#00897B'],
        ];

        $existing = Color::all()->keyBy(fn (Color $c) => $c->getTranslation('name', 'en'));

        foreach ($colors as $index => [$en, $az, $ru, $tr, $hex]) {
            $payload = [
                'name' => ['az' => $az, 'en' => $en, 'ru' => $ru, 'tr' => $tr],
                'hex' => $hex,
                'is_active' => true,
                'sort_order' => $index,
            ];

            if ($color = $existing->get($en)) {
                $color->update($payload);
            } else {
                Color::create($payload);
            }
        }
    }
}
