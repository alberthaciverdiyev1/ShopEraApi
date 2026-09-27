<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An optional point on the map next to a delivery address.
 *
 * The address fields stay exactly as they are and stay required; the point is
 * something the customer may add so a courier can find the door. Every column
 * is nullable, so an address saved by an older app is unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('unit_floor_apartment');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            // Xəritədən seçilən yerin adı — kuryerə göstərmək üçün.
            $table->string('location_label')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_label']);
        });
    }
};
