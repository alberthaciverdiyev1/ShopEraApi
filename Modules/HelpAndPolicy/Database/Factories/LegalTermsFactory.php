<?php

namespace Modules\HelpAndPolicy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpAndPolicy\Entities\LegalTerm;

class LegalTermsFactory extends Factory
{
    protected $model = LegalTerm::class;

    public function definition(): array
    {
        $faker = $this->faker;

        return [
            'html' => [
                'az' => $faker->randomHtml(2, 3),
                'en' => $faker->randomHtml(2, 3),
                'ru' => $faker->randomHtml(2, 3),
                'tr' => $faker->randomHtml(2, 3),
            ],
            'type' => $faker->unique()->randomElement([
                'main_page',
                'register_page',
            ]),
        ];
    }
}
