<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // default ilk record
    public function up(): void
    {
        Schema::create('referral_setting', function (Blueprint $table) {
            $table->id();
            $table->decimal('referral_amount', 8, 2)->default(0.5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('referral_setting')->insert([
            'referral_amount' => 0.5,
            'is_active'       => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_setting');
    }
};
