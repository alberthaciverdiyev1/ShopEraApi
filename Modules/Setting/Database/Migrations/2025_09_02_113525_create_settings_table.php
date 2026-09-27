<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_url')->default('https://instagram.com')->nullable();
            $table->string('tiktok_url')->default('https://tiktok.com')->nullable();
            $table->string('whatsapp_number')->default('994709990569')->nullable();
            $table->string('phone_number_1')->default('0709990569')->nullable();
            $table->string('phone_number_2')->default('0709990569')->nullable();
            $table->string('phone_number_3')->default('0709990569')->nullable();
            $table->string('phone_number_4')->default('0709990569')->nullable();
            $table->string('google_map_url')->default('https://maps.app.goo.gl/2ixKnqaSq5AeoXsu8')->nullable();
            $table->string('address')->default('Baku')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
