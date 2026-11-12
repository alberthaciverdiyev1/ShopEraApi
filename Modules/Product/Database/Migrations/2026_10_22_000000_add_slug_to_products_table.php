<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TRANSLIT = [
        'ə' => 'e', 'ğ' => 'g', 'ş' => 's', 'ç' => 'c', 'ö' => 'o', 'ü' => 'u', 'ı' => 'i',
        'Ə' => 'e', 'Ğ' => 'g', 'Ş' => 's', 'Ç' => 'c', 'Ö' => 'o', 'Ü' => 'u', 'İ' => 'i',
    ];

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('sku');
            }
        });

        // Backfill existing products.
        $used = [];
        foreach (DB::table('products')->select('id', 'title', 'slug')->orderBy('id')->get() as $row) {
            if (! empty($row->slug)) {
                $used[$row->slug] = true;
                continue;
            }
            $title = json_decode((string) $row->title, true) ?: [];
            $base = Str::slug(strtr($title['az'] ?? $title['en'] ?? '', self::TRANSLIT)) ?: 'elan';
            $slug = $base;
            $i = 2;
            while (isset($used[$slug])) {
                $slug = $base.'-'.$i++;
            }
            $used[$slug] = true;
            DB::table('products')->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
