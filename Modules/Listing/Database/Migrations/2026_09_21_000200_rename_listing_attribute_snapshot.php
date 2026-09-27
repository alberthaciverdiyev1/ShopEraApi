<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `listings.attributes` was a trap waiting to be sprung: Eloquent keeps every
 * loaded column in a protected property of that exact name, so inside the
 * model `$this->attributes` means the raw column bag, not the snapshot. The
 * column is renamed before any code reads it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->renameColumn('attributes', 'attribute_values');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->renameColumn('attribute_values', 'attributes');
        });
    }
};
