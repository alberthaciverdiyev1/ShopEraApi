<?php

namespace Modules\Brand\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Brand\Http\Entities\Brand;

class BrandFactory extends Factory
{
protected $model = Brand::class;
    public function definition()
    {
      return [
          'name' => $this->faker->name(),
          'image' => 'https://i.ibb.co/CK4B2Twd/usta.png',
      ];
    }
}
