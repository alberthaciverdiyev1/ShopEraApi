<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PALETTE = [
        '--theme' => ['#06B6D4', 'Primary accent'],
        '--theme-rgb' => ['6, 182, 212', 'Primary accent RGB'],
        '--title' => ['#07111F', 'Heading text'],
        '--title2' => ['#111827', 'Secondary heading text'],
        '--text' => ['#526071', 'Body text'],
        '--text2' => ['#8792A2', 'Muted text'],
        '--text3' => ['#64748B', 'Secondary muted text'],
        '--body' => ['#F7FAFC', 'Page background'],
        '--white' => ['#ffffff', 'Surface background'],
        '--black' => ['#000000', 'Black'],
        '--border' => ['rgba(7, 17, 31, 0.18)', 'Default border'],
        '--border-2' => ['rgba(255, 255, 255, 0.28)', 'Light border'],
        '--border-3' => ['#DDE7EF', 'Neutral border'],
        '--border-4' => ['#D4E0EA', 'Soft border'],
        '--border-5' => ['#334155', 'Dark border'],
        '--border-6' => ['#E4EDF5', 'Subtle border'],
        '--bg-1' => ['#ECFEFF', 'Section background 1'],
        '--bg-2' => ['#F3F7FA', 'Section background 2'],
        '--bg-3' => ['#F0FDFA', 'Section background 3'],
        '--bg-6' => ['#F8FBFF', 'Warm section background'],
        '--bg-7' => ['rgba(7, 17, 31, 0.72)', 'Overlay background'],
        '--bg-8' => ['#EEF6FA', 'Neutral section background'],
        '--bg-9' => ['#DFF7FF', 'Tint background'],
        '--bg-10' => ['#E0F7FA', 'Icon circle background'],
        '--green-gray' => ['#06353A', 'Deep green text'],
        '--orange' => ['#F97316', 'Highlight orange'],
        '--orange2' => ['#FB923C', 'Highlight orange 2'],
        '--orange3' => ['#FDBA74', 'Highlight orange 3'],
        '--box-shadow' => ['0px 18px 45px 0px rgba(7, 17, 31, 0.08)', 'Default shadow'],
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
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // The previous palette was editable by admins, so rolling back should
        // not guess and overwrite their colours.
    }
};
