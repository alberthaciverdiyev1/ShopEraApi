<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_owner_id')->constrained('site_owners')->cascadeOnDelete();
            $table->string('host')->unique(); // e.g. redbull.shopera.test
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('ssl_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('control')->dropIfExists('domains');
    }
};
