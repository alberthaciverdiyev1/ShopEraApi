<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Separates what a person composed in the admin panel from what the
            // application sent by itself. Without it the admin's own list fills
            // up with every customer's order update.
            $table->string('source', 20)->default('system')->after('all')->index();
        });

        // Backfill: everything the system sends carries a data.type marker,
        // the admin panel's own messages never did.
        DB::table('notifications')
            ->whereNull('data')
            ->update(['source' => 'admin']);

        DB::table('notifications')
            ->whereNotNull('data')
            ->whereRaw("data::jsonb ->> 'type' IS NULL")
            ->update(['source' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
