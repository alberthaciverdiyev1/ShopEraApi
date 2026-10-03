<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the default support WhatsApp number used by tenant upgrade pages.
 * updateOrInsert keeps any value already set in the panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('control')->table('settings')->updateOrInsert(
            ['key' => 'support_whatsapp'],
            [
                'value' => '+994709990569',
                'label' => 'Dəstək WhatsApp nömrəsi',
                'group' => 'support',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // Keep the value on rollback — it is user-editable data.
    }
};
