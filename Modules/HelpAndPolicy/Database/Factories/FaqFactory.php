<?php

namespace Modules\HelpAndPolicy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpAndPolicy\Http\Entities\Faq;

class FaqFactory extends Factory
{
protected $model = Faq::class;
    public function definition()
    {
       return [
              'title' => $this->faker->sentence(),
              'description' => $this->faker->paragraph(),
              'type' => $this->faker->randomElement(['general', 'billing', 'technical', 'account']),
       ];
    }
}
