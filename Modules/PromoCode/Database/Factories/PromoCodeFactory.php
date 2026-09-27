<?php

namespace Modules\PromoCode\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Http\Requests\ProductAddRequest;
use Modules\PromoCode\Http\Entities\PromoCode;

class PromoCodeFactory extends Factory
{
protected $model = PromoCode::class;
    public function definition()
    {
      return [
            'code' => strtoupper($this->faker->bothify('??##??##')),
            'discount_percent' => $this->faker->numberBetween(5, 50),
            'user_count' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean(80),
      ];
    }
}
