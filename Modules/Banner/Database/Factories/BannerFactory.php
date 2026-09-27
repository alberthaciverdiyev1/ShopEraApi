<?php

namespace Modules\Banner\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Banner\Http\Entities\Banner;

class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition()
    {
        $images = [
            'https://www.autoshippers.co.uk/blog/wp-content/uploads/bugatti-centodieci-696x392.jpg',
            'https://www.autoshippers.co.uk/blog/wp-content/uploads/1963-ferrari-250-gto-1024x523.jpg',
            'https://www.autoshippers.co.uk/blog/wp-content/uploads/lamborghini-veneno-roadster.jpg',
            'https://www.autoshippers.co.uk/blog/wp-content/uploads/koenigsegg-ccxr-trevita.jpg'
        ];
        $types = ['home', 'favorite', 'basket'];

        return [
            'image' => $images[array_rand($images)],
            'type' => $types[array_rand($types)]
        ];
    }
}
