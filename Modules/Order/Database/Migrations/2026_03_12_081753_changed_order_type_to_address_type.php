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
        Schema::table('orders', function (Blueprint $table) {

            if (! Schema::hasColumn('orders', 'address_type')) {
                $table->string('address_type')->nullable()->after('address_id');
            }

            if (! Schema::hasColumn('orders', 'address_type_id')) {
                $table->string('address_type_id')->nullable()->after('address_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['address_type', 'address_type_id']);
        });
    }
};
