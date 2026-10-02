<?php

namespace Modules\Banner\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Banner\Entities\Banner;
use Modules\Product\Entities\Product;

class BannerDatabaseSeeder extends Seeder
{
    /**
     * Home / favourites / basket banners, each linked to a real product so
     * tapping the banner opens something that actually exists.
     */
    public function run(): void
    {
        $image = fn (string $id) => 'https://images.unsplash.com/photo-'.$id.'?auto=format&fit=crop&w=1600&q=80';

        $find = fn (string $title) => Product::query()
            ->where('title->en', $title)
            ->value('id');

        $banners = [
            [
                'type' => 'home',
                'image' => $image('1441986300917-64674bd600d8'),
                'product_title' => 'Apple iPhone 15 Pro 256GB',
                'url' => null,
            ],
            [
                'type' => 'home',
                'image' => $image('1483985988355-763728e1935b'),
                'product_title' => 'Zara Floral Midi Dress',
                'url' => null,
            ],
            [
                'type' => 'home',
                'image' => $image('1556909114-f6e7ad7d3136'),
                'product_title' => 'Philips Airfryer XL',
                'url' => null,
            ],
            [
                'type' => 'favorite',
                'image' => $image('1542291026-7eec264c27ff'),
                'product_title' => "Nike Air Force 1 '07",
                'url' => null,
            ],
            [
                'type' => 'basket',
                'image' => $image('1556742049-0cfed4f6a45d'),
                'product_title' => null,
                'url' => '/cart',
            ],
        ];

        foreach ($banners as $data) {
            Banner::updateOrCreate(
                ['image' => $data['image']],
                [
                    'type' => $data['type'],
                    'url' => $data['url'],
                    'product_id' => $data['product_title'] ? $find($data['product_title']) : null,
                    'is_active' => true,
                ]
            );
        }
    }
}
