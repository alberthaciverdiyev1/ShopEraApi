<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Storefront URLs and filter query strings are slug-based, so every catalog
     * entity that used to be addressed by its numeric id needs a slug. Slugs
     * are backfilled from the Azerbaijani name/title and de-duplicated per
     * table; the models keep them in sync afterwards (App\Core\Concerns\HasSlug).
     */
    private const TABLES = [
        'categories' => 'name',
        'brands' => 'name',
        'colors' => 'name',
        'sizes' => 'name',
        'filters' => 'title',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $column) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'slug')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('slug')->nullable()->after('id');
            });

            $used = [];

            foreach (DB::table($table)->select('id', $column)->orderBy('id')->get() as $row) {
                $raw = $row->{$column};

                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    $raw = is_array($decoded) ? ($decoded['az'] ?? reset($decoded)) : $raw;
                }

                $base = Str::slug((string) $raw) ?: 'item-'.$row->id;
                $slug = $base;
                $suffix = 2;

                while (isset($used[$slug])) {
                    $slug = $base.'-'.$suffix++;
                }

                $used[$slug] = true;

                DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unique('slug');
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            if (! Schema::hasColumn($table, 'slug')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropUnique(['slug']);
                $blueprint->dropColumn('slug');
            });
        }
    }
};
