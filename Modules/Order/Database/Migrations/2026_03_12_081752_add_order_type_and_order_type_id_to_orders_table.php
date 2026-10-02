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
            if (Schema::hasColumn('orders', 'address_id')) {
                $table->unsignedBigInteger('address_id')->nullable()->change();
            } else {
                $table->unsignedBigInteger('address_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type')->nullable()->after('address_id');
            }

            if (! Schema::hasColumn('orders', 'order_type_id')) {
                $table->string('order_type_id')->nullable()->after('order_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['address_id', 'order_type', 'order_type_id']);
        });
    }
};
