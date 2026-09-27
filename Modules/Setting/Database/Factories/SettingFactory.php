<?php

namespace Modules\Setting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Setting\Http\Entities\Setting;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition()
    {
        return [
            'whatsapp_number' => $this->faker->optional()->phoneNumber(),
            'phone_number_1'  => $this->faker->optional()->phoneNumber(),
            'phone_number_2'  => $this->faker->optional()->phoneNumber(),
            'phone_number_3'  => $this->faker->optional()->phoneNumber(),
            'phone_number_4'  => $this->faker->optional()->phoneNumber(),
            'google_map_url'  => $this->faker->optional()->url(),
        ];
    }
}
