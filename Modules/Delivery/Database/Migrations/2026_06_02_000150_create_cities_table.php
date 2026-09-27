<?php

use App\Enums\City as LegacyCity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        foreach (LegacyCity::list() as $key => $name) {
            DB::table('cities')->insertOrIgnore([
                'key' => $key,
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $historicalKeys = DB::table('user_addresses')
            ->whereNotNull('city')
            ->pluck('city')
            ->merge(DB::table('delivery_prices')->whereNotNull('city_name')->pluck('city_name'))
            ->filter()
            ->unique();

        foreach ($historicalKeys as $key) {
            DB::table('cities')->insertOrIgnore([
                'key' => $key,
                'name' => $key,
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
