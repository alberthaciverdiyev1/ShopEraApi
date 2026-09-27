<?php

namespace Modules\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\Product\Http\Entities\Product;

class UserFactory extends Factory
{
    protected $model = \Modules\User\Http\Entities\User::class;

    public function definition()
    {
        return [
            'name'              => $this->faker->name(),
            'email'             => $this->faker->unique()->safeEmail(),
            'password'          => bcrypt('123456'),
            'email_verified_at' => Carbon::now(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($user) {
            $products = Product::factory()->count(rand(1, 5))->create();
            $user->favorites()->sync($products->pluck('id')->toArray());
        });
    }
}
