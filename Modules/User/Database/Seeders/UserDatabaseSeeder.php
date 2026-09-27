<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Modules\User\Http\Entities\User;
use Modules\Balance\Http\Entities\Balance;
use App\Enums\BalanceType;
use Spatie\Permission\Models\Role;

class UserDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $userRole  = Role::where('name', 'user')->first();

//        User::factory()->count(50)->create()->each(function ($user) use ($userRole) {
//            $user->assignRole($userRole);
//        });

        $admin = User::create([
            'name'              => 'albert haciverdiyev',
            'email'             => 'alberthaciverdiyev55@gmail.com',
            'password'          => Hash::make('123456'),
            'email_verified_at' => Carbon::now(),
        ]);
        $admin->assignRole($adminRole);

        $user = User::create([
            'name'              => 'maharram paputu',
            'email'             => 'polad.aliyevv98@gmail.com',
            'password'          => Hash::make('123456'),
            'email_verified_at' => Carbon::now(),
        ]);
        $user->assignRole($adminRole);

        $user_mehdi = User::create([
            'name'              => 'mehdi ali3v',
            'email'             => 'mehdi@gmail.com',
            'password'          => Hash::make('123456'),
            'email_verified_at' => Carbon::now(),
        ]);
        $user_fatima = User::create([
            'name'              => 'Fatima KHANIMMM',
            'email'             => 'fatima@gmail.com',
            'password'          => Hash::make('123456'),
            'email_verified_at' => Carbon::now(),
        ]);
        $user_fatima->assignRole($adminRole);
        $user_mehdi->assignRole($adminRole);

        $users = User::all();

        foreach ($users as $user) {
            foreach (BalanceType::cases() as $type) {

                $recordsPerType = rand(3, 8);

                for ($i = 0; $i < $recordsPerType; $i++) {
                    $amount = match ($type) {
                        BalanceType::DEPOSIT    => fake()->randomFloat(2, 100, 1000),
                        BalanceType::WITHDRAWAL => fake()->randomFloat(2, 50, 800),
                        BalanceType::REFUND     => fake()->randomFloat(2, 20, 300),
                        BalanceType::BONUS      => fake()->randomFloat(2, 10, 200),
                        BalanceType::WAITING    => fake()->randomFloat(2, 10, 200),
                    };

                    $createdAt = fake()->dateTimeBetween('-90 days', 'now');

                    Balance::create([
                        'user_id'    => $user->id,
                        'type'       => $type->value,
                        'amount'     => $amount,
                        'note'       => ucfirst($type->value) . " test transaction for {$user->name}",
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }
        }

    }
}
