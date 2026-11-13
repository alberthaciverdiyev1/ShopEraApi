<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Accent palette switched from cyan to the logo greens: the primary accent
     * is #16A34A with green-tinted section backgrounds.
     */
    private const PALETTE = [
        '--theme' => ['#16A34A', 'Primary accent'],
        '--theme-rgb' => ['22, 163, 74', 'Primary accent RGB'],
        '--body' => ['#F6FBF7', 'Page background'],
        '--border-6' => ['#D1FAE5', 'Subtle border'],
        '--bg-1' => ['#ECFDF5', 'Section background 1'],
        '--bg-3' => ['#F0FDF4', 'Section background 3'],
        '--bg-6' => ['#F0FDF4', 'Warm section background'],
        '--bg-8' => ['#ECFDF5', 'Neutral section background'],
        '--bg-9' => ['#DCFCE7', 'Tint background'],
        '--bg-10' => ['#DCFCE7', 'Icon circle background'],
        '--green-gray' => ['#052E16', 'Deep green text'],
    ];

    public function up(): void
    {
        foreach (self::PALETTE as $key => [$value, $label]) {
            DB::table('theme_colors')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'label' => $label,
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Colours are editable by admins, so a rollback must not overwrite
        // whatever palette they have chosen since.
    }
};
