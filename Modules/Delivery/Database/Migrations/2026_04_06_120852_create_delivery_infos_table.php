<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB; // DB facade ekledik
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_infos', function (Blueprint $table) {
            $table->id();
            $table->json('description')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        $defaults = [
            ['type' => 'STANDARD', 'description' => json_encode(['en' => 'Standard', 'tr' => 'Standart'])],
            ['type' => 'STANDARD_FAST', 'description' => json_encode(['en' => 'Standard Fast', 'tr' => 'Hızlı Standart'])],
            ['type' => 'PICKUP_POINT', 'description' => json_encode(['en' => 'Pickup Point', 'tr' => 'Teslim Noktası'])],
            ['type' => 'TAKE_FROM_STORE', 'description' => json_encode(['en' => 'Take From Store', 'tr' => 'Mağazadan Teslim'])],
        ];

        DB::table('delivery_infos')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_infos');
    }
};
