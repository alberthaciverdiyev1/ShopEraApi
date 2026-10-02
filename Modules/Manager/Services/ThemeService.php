<?php

namespace Modules\Manager\Services;

use Modules\Manager\Entities\SiteOwner;
use Modules\Manager\Entities\Theme;
use Illuminate\Database\Eloquent\Collection;

class ThemeService
{
    /** Canonical palette keys + default values/labels. */
    public const DEFAULT_PALETTE = [
        '--theme' => ['#06B6D4', 'Əsas rəng (vurğu)'],
        '--theme-rgb' => ['6, 182, 212', 'Əsas rəng — RGB'],
        '--title' => ['#07111F', 'Başlıq mətni'],
        '--title2' => ['#111827', 'İkinci başlıq mətni'],
        '--text' => ['#526071', 'Əsas mətn'],
        '--text2' => ['#8792A2', 'Solğun mətn'],
        '--text3' => ['#64748B', 'İkinci solğun mətn'],
        '--body' => ['#F7FAFC', 'Səhifə fonu'],
        '--white' => ['#ffffff', 'Kart / səth fonu'],
        '--black' => ['#000000', 'Qara'],
        '--border' => ['rgba(7, 17, 31, 0.18)', 'Standart haşiyə'],
        '--border-2' => ['rgba(255, 255, 255, 0.28)', 'Açıq haşiyə'],
        '--border-3' => ['#DDE7EF', 'Neytral haşiyə'],
        '--border-4' => ['#D4E0EA', 'Yumşaq haşiyə'],
        '--border-5' => ['#334155', 'Tünd haşiyə'],
        '--border-6' => ['#E4EDF5', 'İncə haşiyə'],
        '--bg-1' => ['#ECFEFF', 'Bölmə fonu 1'],
        '--bg-2' => ['#F3F7FA', 'Bölmə fonu 2'],
        '--bg-3' => ['#F0FDFA', 'Bölmə fonu 3'],
        '--bg-6' => ['#F8FBFF', 'İsti bölmə fonu'],
        '--bg-7' => ['rgba(7, 17, 31, 0.72)', 'Overlay fonu'],
        '--bg-8' => ['#EEF6FA', 'Neytral bölmə fonu'],
        '--bg-9' => ['#DFF7FF', 'Tint fonu'],
        '--bg-10' => ['#E0F7FA', 'Icon dairə fonu'],
        '--green-gray' => ['#06353A', 'Dərin yaşıl mətn'],
        '--orange' => ['#F97316', 'Vurğu narıncı'],
        '--orange2' => ['#FB923C', 'Vurğu narıncı 2'],
        '--orange3' => ['#FDBA74', 'Vurğu narıncı 3'],
        '--box-shadow' => ['0px 18px 45px 0px rgba(7, 17, 31, 0.08)', 'Kölgə'],
    ];

    public function labels(): array
    {
        return collect(self::DEFAULT_PALETTE)->map(fn ($v) => $v[1])->all();
    }

    /** @return Collection<int,Theme> */
    public function themes()
    {
        return Theme::query()->active()->orderBy('sort_order')->get();
    }

    public function defaultTheme(): ?Theme
    {
        return Theme::query()->where('is_default', true)->first()
            ?? Theme::query()->active()->orderBy('sort_order')->first();
    }

    /** @return array<string,string> */
    public function paletteForTheme(?Theme $theme): array
    {
        if (! $theme) {
            return collect(self::DEFAULT_PALETTE)->map(fn ($v) => $v[0])->all();
        }

        $this->ensurePalette($theme);

        return $theme->colors()->pluck('value', 'key')->all();
    }

    /** Selected theme (or default) merged with the owner's own overrides. */
    public function forOwner(?SiteOwner $owner): array
    {
        $theme = $owner?->theme ?? $this->defaultTheme();
        $colors = $this->paletteForTheme($theme);

        if (empty($colors)) {
            $colors = collect(self::DEFAULT_PALETTE)->map(fn ($v) => $v[0])->all();
        }

        if ($owner) {
            foreach ($owner->themeColors()->whereNull('theme_id')->pluck('value', 'key') as $key => $value) {
                $colors[$key] = $value;
            }
        }

        return $colors;
    }

    public function createTheme(array $data, array $colors = []): Theme
    {
        $theme = Theme::query()->create($data);
        $this->ensurePalette($theme, $colors);

        if ($theme->is_default) {
            Theme::query()->where('id', '!=', $theme->id)->update(['is_default' => false]);
        }

        return $theme;
    }

    /** Fill in any missing palette keys using the provided base values. */
    public function ensurePalette(Theme $theme, array $base = []): void
    {
        foreach (self::DEFAULT_PALETTE as $key => [$value, $label]) {
            $theme->colors()->firstOrCreate(
                ['key' => $key],
                ['value' => $base[$key] ?? $value, 'label' => $label]
            );
        }
    }

    public function updatePalette(Theme $theme, array $colors): void
    {
        foreach ($colors as $key => $value) {
            if (! is_string($key) || ! is_string($value) || $value === '') {
                continue;
            }

            $theme->colors()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'label' => self::DEFAULT_PALETTE[$key][1] ?? null]
            );
        }
    }

    public function makeDefault(Theme $theme): void
    {
        Theme::query()->where('id', '!=', $theme->id)->update(['is_default' => false]);
        $theme->update(['is_default' => true, 'is_active' => true]);
    }
}
