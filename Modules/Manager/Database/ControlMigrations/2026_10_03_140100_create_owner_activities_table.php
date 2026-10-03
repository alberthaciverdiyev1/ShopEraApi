<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->create('owner_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_owner_id')->nullable()->constrained('site_owners')->cascadeOnDelete();
            $table->string('action');
            $table->string('description')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::connection('control')->dropIfExists('owner_activities');
    }
};
