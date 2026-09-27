<?php

namespace Modules\Color\Database\Factories;

use Modules\Color\Http\Entities\Color;

class ColorFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Color::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->colorName(),
            'hex' => $this->faker->hexColor(),
            'is_active' => $this->faker->boolean(),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
