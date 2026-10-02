<?php

namespace Modules\Delivery\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Delivery\Entities\City;
use Modules\Delivery\Entities\Delivery;

class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    public function definition(): array
    {
        $cities = City::query()->active()->pluck('key')->all();

        return [
            'city_name' => $this->faker->unique()->randomElement($cities),
            'price' => $this->faker->randomFloat(2, 2, 50),
            'free_from' => $this->faker->randomElement([0, 30, 50, 100]),
            'delivery_time' => $this->faker->randomElement(['1-2 gün', '2-3 gün', '3-5 gün']),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
