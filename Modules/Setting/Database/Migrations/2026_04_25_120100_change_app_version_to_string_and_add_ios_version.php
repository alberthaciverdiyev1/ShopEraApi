<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('app_version', 20)->default('1.0.0')->change();
            $table->string('app_version_ios', 20)->default('1.0.0')->change();
        });
    }
    
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('app_version')->default(1.1)->change();
            $table->decimal('app_version_ios')->default(1.0)->change();
        });
    }
};
