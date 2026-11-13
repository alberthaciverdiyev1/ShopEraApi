<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which size family a category offers. Children inherit the value from
     * their parents, so only the root categories need one.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'size_group')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('size_group', 50)->nullable()->after('parent_id');
            });
        }

        // Existing roots: clothing, shoes and perfumes (sold by volume).
        $roots = [
            'qadin-geyimi-3' => 'clothing',
            'kisi-geyimi-3' => 'clothing',
            'usaq-geyimi-4' => 'clothing',
            'idman-geyimi-2' => 'clothing',
            'ayaqqabi-3' => 'shoes',
            'etirler-2' => 'volume',
        ];

        foreach ($roots as $slug => $group) {
            DB::table('categories')
                ->where('slug', $slug)
                ->whereNull('size_group')
                ->update(['size_group' => $group]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('categories', 'size_group')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('size_group');
        });
    }
};
