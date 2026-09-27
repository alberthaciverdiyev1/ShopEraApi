<?php

namespace Modules\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Category\Http\Entities\Category;

class CategoryFactory extends Factory
{
protected $model = Category::class;

    public function definition()
    {
        $images = [
            'https://www.collinsdictionary.com/images/thumb/apple_158989157_250.jpg?version=6.0.86',
            'https://www.thegoodmoodfood.com.au/siteassets/foods/green/pear-lg.png',
            'https://www.quanta.org/thumbs/thumb-orange-640x480-orange.jpg',
            'https://source.washu.edu/app/uploads/2015/11/Tomato250.jpg',
            'https://www.alvinesa.com/wp-content/uploads/2024/07/2-1-1024x683.png',
            'https://images.immediate.co.uk/production/volatile/sites/30/2025/03/Bunch-of-bananas-00871a2.jpg?quality=90&webp=true&resize=440,400',
            'https://www.dole.com/sites/default/files/styles/1536w1152h-webp-80/public/media/2025-01/strawberries.png.webp?itok=8xtcMvlb'
        ];

        return [
            'name'        => $this->faker->unique()->words(2, true),
            'image'       => $this->faker->randomElement($images),
            'description' => $this->faker->sentence(10),
            'parent_id'   => null,
        ];
    }

}
