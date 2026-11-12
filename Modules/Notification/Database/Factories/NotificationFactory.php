<?php

namespace Modules\Notification\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Entities\Notification;
use Modules\User\Entities\User;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition()
    {
        $userId = $this->faker->boolean(80)
            ? User::inRandomOrder()->value('id')
            : null;

        return [
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'icon' => 'https://developer.apple.com/assets/elements/icons/notifications/notifications-128x128_2x.png',
            'user_id' => $userId,
            'all' => $userId === null,
        ];

    }
}
