<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Entities\ThemeColor;

class ThemeColorDatabaseSeeder extends Seeder
{
    /**
     * Default palette — mirrors the theme's :root variables.
     */
    public function run(): void
    {
        ThemeColor::whereIn('key', [
            ...array_map(fn (int $index) => "--theme{$index}", range(2, 9)),
            '--'.'Theme-Color-2',
        ])->delete();

        $defaults = [
            '--theme' => ['#16A34A', 'Primary accent'],
            '--theme-rgb' => ['22, 163, 74', 'Primary accent RGB'],
            '--title' => ['#07111F', 'Heading text'],
            '--title2' => ['#111827', 'Secondary heading text'],
            '--text' => ['#526071', 'Body text'],
            '--text2' => ['#8792A2', 'Muted text'],
            '--text3' => ['#64748B', 'Secondary muted text'],
            '--body' => ['#F6FBF7', 'Page background'],
            '--white' => ['#ffffff', 'Surface background'],
            '--black' => ['#000000', 'Black'],
            '--border' => ['rgba(7, 17, 31, 0.18)', 'Default border'],
            '--border-2' => ['rgba(255, 255, 255, 0.28)', 'Light border'],
            '--border-3' => ['#DDE7EF', 'Neutral border'],
            '--border-4' => ['#D4E0EA', 'Soft border'],
            '--border-5' => ['#334155', 'Dark border'],
            '--border-6' => ['#D1FAE5', 'Subtle border'],
            '--bg-1' => ['#ECFDF5', 'Section background 1'],
            '--bg-2' => ['#F3F7FA', 'Section background 2'],
            '--bg-3' => ['#F0FDF4', 'Section background 3'],
            '--bg-6' => ['#F0FDF4', 'Warm section background'],
            '--bg-7' => ['rgba(7, 17, 31, 0.72)', 'Overlay background'],
            '--bg-8' => ['#ECFDF5', 'Neutral section background'],
            '--bg-9' => ['#DCFCE7', 'Tint background'],
            '--bg-10' => ['#DCFCE7', 'Icon circle background'],
            '--green-gray' => ['#052E16', 'Deep green text'],
            '--orange' => ['#F97316', 'Highlight orange'],
            '--orange2' => ['#FB923C', 'Highlight orange 2'],
            '--orange3' => ['#FDBA74', 'Highlight orange 3'],
            '--box-shadow' => ['0px 18px 45px 0px rgba(7, 17, 31, 0.08)', 'Default shadow'],
        ];

        foreach ($defaults as $key => [$value, $label]) {
            ThemeColor::updateOrCreate(['key' => $key], ['value' => $value, 'label' => $label]);
        }
    }
}
