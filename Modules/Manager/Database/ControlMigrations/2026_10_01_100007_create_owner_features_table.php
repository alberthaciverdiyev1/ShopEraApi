<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->create('owner_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_owner_id')->constrained('site_owners')->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->string('value')->nullable();
            $table->timestamps();
            $table->unique(['site_owner_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('control')->dropIfExists('owner_features');
    }
};
