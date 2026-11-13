<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Size families a category can offer (clothing, shoes, volume, …). */
    public function up(): void
    {
        if (! Schema::hasColumn('sizes', 'size_group')) {
            Schema::table('sizes', function (Blueprint $table) {
                $table->string('size_group', 50)->nullable()->after('name');
            });
        }

        $this->classifyExistingSizes();
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sizes', 'size_group')) {
            return;
        }

        Schema::table('sizes', function (Blueprint $table) {
            $table->dropColumn('size_group');
        });
    }

    /**
     * Tag the sizes that already exist so a dress no longer offers shoe sizes:
     * letter sizes are clothing, two-digit numbers are shoes, the rest (e.g.
     * "One Size") stay universal and apply to every category.
     */
    private function classifyExistingSizes(): void
    {
        $clothing = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '2XL', '3XL', '4XL'];

        foreach (DB::table('sizes')->orderBy('id')->get() as $size) {
            $names = json_decode((string) $size->name, true);
            $names = is_array($names) ? $names : [(string) $size->name];

            $group = null;

            foreach ($names as $name) {
                $label = mb_strtoupper(trim((string) $name));

                if (in_array($label, $clothing, true)) {
                    $group = 'clothing';
                    break;
                }

                if (preg_match('/^\d{2}(?:\.\d)?$/', $label)) {
                    $group = 'shoes';
                }
            }

            if ($group !== null) {
                DB::table('sizes')->where('id', $size->id)->update(['size_group' => $group]);
            }
        }
    }
};
