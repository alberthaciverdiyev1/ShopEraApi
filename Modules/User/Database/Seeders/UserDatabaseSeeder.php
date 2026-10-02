<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Modules\Delivery\Entities\City;
use Modules\Product\Entities\Product;
use Modules\User\Entities\Address;
use Modules\User\Entities\ReferralCode;
use Modules\User\Entities\User;
use Modules\User\Entities\UserReferral;
use Spatie\Permission\Models\Role;

class UserDatabaseSeeder extends Seeder
{
    /**
     * Team accounts, a realistic customer base, their delivery addresses,
     * favourites and referral links — all interlinked by real ids.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        // ---- Team / staff accounts -------------------------------------
        $staff = [
            ['albert haciverdiyev', 'alberthaciverdiyev55@gmail.com', '0500000001', 'admin'],
            ['maharram paputu', 'polad.aliyevv98@gmail.com', '0500000002', 'admin'],
            ['mehdi aliyev', 'mehdi@gmail.com', '0500000003', 'admin'],
            ['Fatima Khanim', 'fatima@gmail.com', '0500000004', 'admin'],
        ];

        foreach ($staff as [$name, $email, $phone, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'password' => Hash::make('123456'),
                    'email_verified_at' => Carbon::now(),
                    'is_active' => true,
                ]
            );
            $user->assignRole($role === 'admin' ? $adminRole : $userRole);
        }

        // ---- Customers -------------------------------------------------
        $customers = [
            ['Elvin', 'Məmmədov', 'elvin.mammadov@gmail.com', '0501234567', 'Baku', 'Nəsimi rayonu', 'Nizami küçəsi 203', '5-ci mərtəbə, mənzil 12', 40.3791290, 49.8466040],
            ['Aysel', 'Əliyeva', 'aysel.aliyeva@gmail.com', '0512345678', 'Baku', 'Yasamal rayonu', 'Şərifzadə küçəsi 145', '3-cü mərtəbə, mənzil 7', 40.3730160, 49.8293810],
            ['Rəşad', 'Hüseynov', 'rashad.huseynov@gmail.com', '0553456789', 'Baku', 'Nərimanov rayonu', 'Təbriz küçəsi 32', '8-ci mərtəbə, mənzil 45', 40.4008630, 49.8696740],
            ['Nigar', 'Səfərova', 'nigar.safarova@gmail.com', '0504567890', 'Baku', 'Xətai rayonu', 'Babək prospekti 78', '2-ci mərtəbə, mənzil 9', 40.3806930, 49.8973390],
            ['Kamran', 'Quliyev', 'kamran.guliyev@gmail.com', '0775678901', 'Sumqayit', '1-ci mikrorayon', 'Koroğlu küçəsi 15', '4-cü mərtəbə, mənzil 22', 40.5897370, 49.6688830],
            ['Leyla', 'Əhmədova', 'leyla.ahmadova@gmail.com', '0516789012', 'Baku', 'Səbail rayonu', 'Nizami küçəsi 90', '6-cı mərtəbə, mənzil 31', 40.3653600, 49.8352040],
            ['Orxan', 'İbrahimov', 'orkhan.ibrahimov@gmail.com', '0557890123', 'Ganja', 'Nizami rayonu', 'Cavadxan küçəsi 47', '1-ci mərtəbə, mənzil 3', 40.6827780, 46.3605560],
            ['Günel', 'Nəsibova', 'gunel.nasibova@gmail.com', '0508901234', 'Baku', 'Binəqədi rayonu', 'Sülh küçəsi 112', '9-cu mərtəbə, mənzil 56', 40.4478140, 49.8016760],
            ['Tural', 'Rəhimov', 'tural.rahimov@gmail.com', '0779012345', 'Baku', 'Sabunçu rayonu', 'Zabrat qəsəbəsi, Məktəbli küçəsi 8', '1-ci mərtəbə, mənzil 2', 40.4516830, 49.9464540],
            ['Sevinc', 'Bağırova', 'sevinc.baghirova@gmail.com', '0510123456', 'Baku', 'Nizami rayonu', 'Qara Qarayev prospekti 55', '7-ci mərtəbə, mənzil 38', 40.4104100, 49.9129230],
            ['Fərid', 'Abbasov', 'farid.abbasov@gmail.com', '0551234098', 'Xirdalan', 'Mərkəz', 'Heydər Əliyev prospekti 21', '3-cü mərtəbə, mənzil 15', 40.4489300, 49.7554120],
            ['Aynur', 'Vəliyeva', 'aynur.valiyeva@gmail.com', '0502345109', 'Baku', 'Xəzər rayonu', 'Mərdəkan qəsəbəsi, Şah İsmayıl Xətai küçəsi 4', '2-ci mərtəbə, mənzil 11', 40.4892830, 50.1430410],
        ];

        $customerUsers = [];

        foreach ($customers as [$name, $surname, $email, $phone, $city, $district, $street, $unit, $lat, $lng]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name.' '.$surname,
                    'phone' => $phone,
                    'password' => Hash::make('123456'),
                    'email_verified_at' => Carbon::now(),
                    'is_active' => true,
                ]
            );
            $user->assignRole($userRole);
            $customerUsers[] = $user;

            Address::updateOrCreate(
                ['user_id' => $user->id, 'street_building_number' => $street],
                [
                    'city' => $city,
                    'town_village_district' => $district,
                    'unit_floor_apartment' => $unit,
                    'is_default' => true,
                    'full_name' => $name.' '.$surname,
                    'contact_number' => $phone,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'location_label' => $district.', '.(City::findMatching($city)?->name ?? $city),
                ]
            );
        }

        // ---- Referral programme ----------------------------------------
        foreach (array_merge(User::whereIn('email', array_column($staff, 1))->get()->all(), $customerUsers) as $user) {
            $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $user->name), 0, 4)).str_pad((string) $user->id, 3, '0', STR_PAD_LEFT);

            ReferralCode::updateOrCreate(
                ['user_id' => $user->id],
                ['referral_code' => $code, 'usage_count' => 0]
            );
        }

        // Link a few customers to an existing referrer so the tree is real.
        $referrer = $customerUsers[0]; // Elvin
        $referrerCode = ReferralCode::where('user_id', $referrer->id)->value('referral_code');

        foreach ([$customerUsers[1], $customerUsers[2], $customerUsers[6]] as $referred) {
            UserReferral::updateOrCreate(
                ['user_id' => $referred->id],
                ['referral_code' => $referrerCode]
            );
        }
        ReferralCode::where('user_id', $referrer->id)->update(['usage_count' => 3]);

        // ---- Favourites & open baskets ---------------------------------
        $productIds = Product::query()->orderBy('id')->pluck('id')->all();

        if ($productIds) {
            foreach ($customerUsers as $index => $user) {
                $picks = array_slice($productIds, ($index * 3) % count($productIds), 4);
                // sync() replaces the set, so re-seeding never accumulates rows.
                $user->favorites()->sync($picks);

                // A couple of items left in the basket (not yet ordered).
                $basketPicks = array_slice($productIds, ($index * 5 + 1) % count($productIds), 2);
                $user->basket()->whereNotIn('product_id', $basketPicks)->delete();
                foreach ($basketPicks as $pid) {
                    $user->basket()->updateOrCreate(
                        ['product_id' => $pid],
                        ['quantity' => 1, 'selected' => true, 'is_ordered' => false]
                    );
                }
            }
        }
    }
}
