<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A photo can belong to one size (e.g. the 42 shot of a shoe), so the
     * details gallery can follow the size selection the same way it follows
     * the colour.
     */
    public function up(): void
    {
        if (Schema::hasColumn('product_image', 'size_id')) {
            return;
        }

        Schema::table('product_image', function (Blueprint $table) {
            $table->foreignId('size_id')
                ->nullable()
                ->after('color_id')
                ->constrained('sizes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_image', 'size_id')) {
            return;
        }

        Schema::table('product_image', function (Blueprint $table) {
            $table->dropConstrainedForeignId('size_id');
        });
    }
};
