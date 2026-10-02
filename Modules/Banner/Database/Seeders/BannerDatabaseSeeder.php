<?php

namespace Modules\Banner\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Banner\Entities\Banner;
use Modules\Product\Entities\Product;

/**
 * Storefront banners. The home page reads the `big` (hero slider), `middle`
 * and `small` (promo strips) types, each linked to a real product so tapping a
 * banner opens something that exists. `favorite`/`basket` are kept for the
 * wishlist/cart pages.
 */
class BannerDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $image = fn (string $id) => 'https://images.unsplash.com/photo-'.$id.'?auto=format&fit=crop&w=1600&q=80';

        $find = fn (string $title) => Product::query()->where('title->en', $title)->value('id');

        $banners = [
            // Hero slider
            ['big', $image('1441986300917-64674bd600d8'), 'Apple iPhone 15 Pro 256GB', null],
            ['big', $image('1483985988355-763728e1935b'), 'Zara Floral Midi Dress', null],
            ['big', $image('1571781926291-c477ebfd024b'), 'Philips Airfryer XL', null],

            // Mid-page promo
            ['middle', $image('1556909114-f6e7ad7d3136'), 'Philips Airfryer XL', null],
            ['middle', $image('1542291026-7eec264c27ff'), "Nike Air Force 1 '07", null],

            // Small promo strips
            ['small', $image('1490481651871-ab68de25d43d'), null, '/shop'],
            ['small', $image('1445205170230-053b83016050'), null, '/shop'],

            // Wishlist / basket
            ['favorite', $image('1542291026-7eec264c27ff'), "Nike Air Force 1 '07", null],
            ['basket', $image('1556742049-0cfed4f6a45d'), null, '/cart'],
        ];

        foreach ($banners as [$type, $imageUrl, $productTitle, $url]) {
            Banner::query()->updateOrCreate(
                ['type' => $type, 'image' => $imageUrl],
                [
                    'url' => $url,
                    'product_id' => $productTitle ? $find($productTitle) : null,
                    'is_active' => true,
                ]
            );
        }
    }
}
