<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Entities\Review;
use Modules\User\Http\Entities\User;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'product_id' => Product::inRandomOrder()->first()->id ?? Product::factory(),
            'rate' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(10),
        ];
    }
}
