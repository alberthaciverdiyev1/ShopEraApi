<?php

namespace Modules\Size\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Size\Http\Entities\Size;

class SizeFactory extends Factory
{
    protected $model = Size::class;

    public function definition()
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        return [
            'name' => $this->faker->unique()->randomElement($sizes),
            'icon' => $this->faker->imageUrl(),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
