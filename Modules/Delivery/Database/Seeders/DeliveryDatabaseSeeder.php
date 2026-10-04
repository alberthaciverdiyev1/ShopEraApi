<?php

namespace Modules\Delivery\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Delivery\Entities\City;
use Modules\Delivery\Entities\Delivery;
use Modules\Delivery\Entities\PickupPoint;

class DeliveryDatabaseSeeder extends Seeder
{
    /**
     * Delivery tariffs for every active city, priced by distance zone from
     * Baku, plus the real pickup points the couriers hand over at.
     */
    public function run(): void
    {
        // Zone 1 — Baku and its closest suburbs.
        $zone1 = ['Baku', 'BakiXetaiRayonu', 'BakiSabayilRayon', 'BakiXezerRayonu', 'BakiNesimiRayon', 'BakiQaradagRayon', 'BakiNizamiRayonu', 'BakiBineqediRayon', 'PirallahiRayonu', 'BakiYasamalRayonu', 'BakiSabuncuRayonu', 'BakiNerimanovRayonu', 'BakiSuraxaniRayon', 'Xirdalan', 'Khirdalan', 'Masazir', 'Mehdiabad'];
        // Zone 2 — Sumqayit, Absheron and nearby towns.
        $zone2 = ['Sumgayit', 'Sumqayit', 'Sumqayit_AZ', 'Abseron', 'SarayQesebe', 'Qobustan', 'Xizi', 'Shamakhi'];
        // Zone 3 — large regional cities.
        $zone3 = ['Ganja', 'Mingachevir', 'Shaki', 'Lankaran', 'Shirvan', 'Naftalan', 'Yevlakh', 'Nakhchivan', 'Agdash', 'Ujar', 'Goychay', 'Salyan'];

        $tariffs = [
            1 => ['price' => 5.00, 'free_from' => 50.00, 'delivery_time' => '1-2 iş günü', 'fast_price' => 10.00, 'fast_delivery_time' => '2-4 saat'],
            2 => ['price' => 8.00, 'free_from' => 80.00, 'delivery_time' => '2-3 iş günü', 'fast_price' => 15.00, 'fast_delivery_time' => 'Ertəsi gün'],
            3 => ['price' => 10.00, 'free_from' => 120.00, 'delivery_time' => '3-4 iş günü', 'fast_price' => 18.00, 'fast_delivery_time' => '1-2 gün'],
        ];
        $default = ['price' => 12.00, 'free_from' => 150.00, 'delivery_time' => '3-5 iş günü', 'fast_price' => 20.00, 'fast_delivery_time' => '2 gün'];

        $now = now();

        // Test/junk entries that live in the legacy city enum but are not real.
        $excluded = ['Simcity', 'Sagol', 'Hayday', 'Albert', 'Uskudar'];
        Delivery::whereIn('city_name', $excluded)->forceDelete();

        $cities = City::query()->active()->whereNotIn('key', $excluded)->get();

        foreach ($cities as $city) {
            $zone = match (true) {
                in_array($city->key, $zone1, true) => 1,
                in_array($city->key, $zone2, true) => 2,
                in_array($city->key, $zone3, true) => 3,
                default => 0,
            };

            $tariff = $zone ? $tariffs[$zone] : $default;

            Delivery::updateOrCreate(
                ['city_name' => $city->key],
                $tariff + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        // ---- Pickup points (real Baku locations) -----------------------
        $pickupPoints = [
            ['28 May filialı', 'Bakı, 28 May küçəsi 42', 3.00, ['az' => '1-2 iş günü', 'en' => '1-2 business days', 'ru' => '1-2 рабочих дня', 'tr' => '1-2 iş günü']],
            ['Nərimanov filialı', 'Bakı, Təbriz küçəsi 30', 3.00, ['az' => '1-2 iş günü', 'en' => '1-2 business days', 'ru' => '1-2 рабочих дня', 'tr' => '1-2 iş günü']],
            ['Elmlər Akademiyası filialı', 'Bakı, Hüseyn Cavid prospekti 12', 3.00, ['az' => '1-2 iş günü', 'en' => '1-2 business days', 'ru' => '1-2 рабочих дня', 'tr' => '1-2 iş günü']],
            ['Gənclik Mall filialı', 'Bakı, Fətəli Xan Xoyski prospekti 71', 4.00, ['az' => 'Ertəsi gün', 'en' => 'Next day', 'ru' => 'На следующий день', 'tr' => 'Ertesi gün']],
            ['Sumqayıt filialı', 'Sumqayıt, 2-ci mikrorayon', 5.00, ['az' => '2-3 iş günü', 'en' => '2-3 business days', 'ru' => '2-3 рабочих дня', 'tr' => '2-3 iş günü']],
        ];

        foreach ($pickupPoints as [$name, $address, $price, $time]) {
            PickupPoint::updateOrCreate(
                ['name' => $name],
                [
                    'address' => $address,
                    'price' => $price,
                    'delivery_time' => $time,
                    'is_active' => true,
                ]
            );
        }
    }
}
