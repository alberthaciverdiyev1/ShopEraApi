<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Listing-specific fields: where it is (city), its condition, and the secret
 * management token that lets a guest edit/delete their own listing without an
 * account. The token is stored hashed; only the URL handed back at creation
 * carries the plain value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('category_id')
                ->constrained('cities')->nullOnDelete();
            $table->string('condition', 20)->nullable()->after('city_id');
            $table->string('manage_token', 64)->nullable()->after('contact_email');
            $table->unique('manage_token');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['manage_token']);
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn(['condition', 'manage_token']);
        });
    }
};
