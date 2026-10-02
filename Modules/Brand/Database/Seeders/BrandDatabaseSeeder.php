<?php

namespace Modules\Brand\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Brand\Entities\Brand;

class BrandDatabaseSeeder extends Seeder
{
    /**
     * Curated catalogue of real brands carried by the marketplace.
     * Logos are served by Clearbit's public logo endpoint (real brand marks).
     */
    public function run(): void
    {
        $brands = [
            ['Samsung', 'samsung.com'],
            ['Apple', 'apple.com'],
            ['Xiaomi', 'mi.com'],
            ['Huawei', 'huawei.com'],
            ['LG', 'lg.com'],
            ['Sony', 'sony.com'],
            ['Philips', 'philips.com'],
            ['Bosch', 'bosch.com'],
            ['Asus', 'asus.com'],
            ['Lenovo', 'lenovo.com'],
            ['HP', 'hp.com'],
            ['Dell', 'dell.com'],
            ['Acer', 'acer.com'],
            ['Canon', 'canon.com'],
            ['Nikon', 'nikon.com'],
            ['JBL', 'jbl.com'],
            ['Bose', 'bose.com'],
            ['Logitech', 'logitech.com'],
            ['Anker', 'anker.com'],
            ['Casio', 'casio.com'],
            ['Fossil', 'fossil.com'],
            ['Nike', 'nike.com'],
            ['Adidas', 'adidas.com'],
            ['Puma', 'puma.com'],
            ['Reebok', 'reebok.com'],
            ['New Balance', 'newbalance.com'],
            ['Converse', 'converse.com'],
            ['Vans', 'vans.com'],
            ['Zara', 'zara.com'],
            ['H&M', 'hm.com'],
            ["Levi's", 'levi.com'],
            ['Mango', 'mango.com'],
            ['Tommy Hilfiger', 'tommy.com'],
            ['Calvin Klein', 'calvinklein.com'],
            ['IKEA', 'ikea.com'],
            ['Tefal', 'tefal.com'],
            ['Dyson', 'dyson.com'],
            ['Braun', 'braun.com'],
            ['Oral-B', 'oralb.com'],
            ['Nivea', 'nivea.com'],
            ["L'Oréal", 'loreal.com'],
            ['Maybelline', 'maybelline.com'],
            ['Garnier', 'garnier.com'],
            ['LEGO', 'lego.com'],
            ['Mattel', 'mattel.com'],
            ['Hasbro', 'hasbro.com'],
            ['Pampers', 'pampers.com'],
            ['Colgate', 'colgate.com'],
            ['Dove', 'dove.com'],
            ['Gillette', 'gillette.com'],
            ['Trek', 'trekbikes.com'],
            ['Giant', 'giant-bicycles.com'],
            ['The North Face', 'thenorthface.com'],
            ['Columbia', 'columbia.com'],
            ['Decathlon', 'decathlon.com'],
            ['Wilson', 'wilson.com'],
            ['Spalding', 'spalding.com'],
            ['Coca-Cola', 'coca-cola.com'],
            ['Nestlé', 'nestle.com'],
            ['Danone', 'danone.com'],
            ['Barilla', 'barilla.com'],
            ['Lavazza', 'lavazza.com'],
            ['Ravensburger', 'ravensburger.com'],
        ];

        $order = count($brands);

        foreach ($brands as [$name, $domain]) {
            Brand::updateOrCreate(
                ['name' => $name],
                [
                    'image' => 'https://logo.clearbit.com/'.$domain.'?size=200',
                    'is_active' => true,
                    'sort_order' => $order--,
                ]
            );
        }
    }
}
