<?php

namespace Modules\Balance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Balance\Http\Entities\Balance;

class BalanceFactory extends Factory
{
    protected $model = Balance::class;

    public function definition()
    {
        return [];
    }
}
