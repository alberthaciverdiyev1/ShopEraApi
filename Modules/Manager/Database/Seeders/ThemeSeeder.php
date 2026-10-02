<?php

namespace Modules\Manager\Database\Seeders;

use Modules\Manager\Entities\Theme;
use Modules\Manager\Services\ThemeService;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ThemeService::class);

        $themes = [
            ['Aero Noir', 'aero-noir', true, 1, 'Cyan vurgulu müasir tema', []],
            ['Aurora', 'aurora', false, 2, 'Bənövşəyi/indigo vurgulu', [
                '--theme' => '#7C3AED', '--theme-rgb' => '124, 58, 237', '--title' => '#1E1B4B',
                '--text' => '#4B5563', '--body' => '#F5F3FF', '--bg-1' => '#EDE9FE', '--bg-2' => '#F5F3FF', '--orange' => '#EC4899',
            ]],
            ['Classic', 'classic', false, 3, 'Yaşıl/narıncı klassik tema', [
                '--theme' => '#16A34A', '--theme-rgb' => '22, 163, 74', '--title' => '#14532D',
                '--text' => '#44403C', '--body' => '#F0FDF4', '--bg-1' => '#DCFCE7', '--bg-2' => '#F0FDF4', '--orange' => '#EA580C',
            ]],
            ['Ocean', 'ocean', false, 4, 'Mavi dəniz çalarlı', [
                '--theme' => '#0EA5E9', '--theme-rgb' => '14, 165, 233', '--title' => '#0C4A6E',
                '--text' => '#475569', '--body' => '#F0F9FF', '--bg-1' => '#E0F2FE', '--bg-2' => '#F0F9FF', '--orange' => '#F97316',
            ]],
            ['Sunset', 'sunset', false, 5, 'Narıncı/qızıl gün batımı', [
                '--theme' => '#F97316', '--theme-rgb' => '249, 115, 22', '--title' => '#7C2D12',
                '--text' => '#57534E', '--body' => '#FFF7ED', '--bg-1' => '#FFEDD5', '--bg-2' => '#FFF7ED', '--orange' => '#DC2626',
            ]],
            ['Rose', 'rose', false, 6, 'Çəhrayı/qırmızı romantik', [
                '--theme' => '#E11D48', '--theme-rgb' => '225, 29, 72', '--title' => '#881337',
                '--text' => '#52525B', '--body' => '#FFF1F2', '--bg-1' => '#FFE4E6', '--bg-2' => '#FFF1F2', '--orange' => '#F59E0B',
            ]],
            ['Emerald', 'emerald', false, 7, 'Zümrüd yaşılı', [
                '--theme' => '#10B981', '--theme-rgb' => '16, 185, 129', '--title' => '#064E3B',
                '--text' => '#475569', '--body' => '#ECFDF5', '--bg-1' => '#D1FAE5', '--bg-2' => '#ECFDF5', '--orange' => '#F97316',
            ]],
            ['Midnight', 'midnight', false, 8, 'Tünd slate / premium', [
                '--theme' => '#334155', '--theme-rgb' => '51, 65, 85', '--title' => '#0F172A',
                '--text' => '#475569', '--body' => '#F8FAFC', '--bg-1' => '#E2E8F0', '--bg-2' => '#F1F5F9', '--orange' => '#0EA5E9',
            ]],
            ['Amber', 'amber', false, 9, 'Amber/qızılı enerjik', [
                '--theme' => '#D97706', '--theme-rgb' => '217, 119, 6', '--title' => '#78350F',
                '--text' => '#57534E', '--body' => '#FFFBEB', '--bg-1' => '#FEF3C7', '--bg-2' => '#FFFBEB', '--orange' => '#B45309',
            ]],
            ['Candy', 'candy', false, 10, 'Fuşiya/şəkər parlaq', [
                '--theme' => '#D946EF', '--theme-rgb' => '217, 70, 239', '--title' => '#701A75',
                '--text' => '#52525B', '--body' => '#FDF4FF', '--bg-1' => '#FAE8FF', '--bg-2' => '#FDF4FF', '--orange' => '#F43F5E',
            ]],
        ];

        foreach ($themes as [$name, $slug, $isDefault, $sort, $description, $colors]) {
            $theme = Theme::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description, 'is_default' => $isDefault, 'is_active' => true, 'sort_order' => $sort]
            );

            $service->ensurePalette($theme, $colors);
            $service->updatePalette($theme, $colors);
        }
    }
}
