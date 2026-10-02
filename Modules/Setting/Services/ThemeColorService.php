<?php

namespace Modules\Setting\Services;

use Illuminate\Http\JsonResponse;
use Modules\Setting\Entities\ThemeColor;

class ThemeColorService
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

    public function __construct(private readonly ThemeColor $model) {}

    /** The whole palette as a `{ "--var": "#hex" }` map. */
    public function all(): JsonResponse
    {
        return responseHelper(__('Theme retrieved successfully.'), 200, [
            'colors' => $this->palette(),
            'items' => $this->model->query()
                ->whereIn('key', array_keys(self::PALETTE))
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function palette(): array
    {
        $this->ensureDefaults();

        return $this->model->query()
            ->whereIn('key', array_keys(self::PALETTE))
            ->orderBy('id')
            ->pluck('value', 'key')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    /**
     * Update one or many colours. Unknown keys are created so the admin can
     * introduce new variables without a migration.
     */
    public function update(array $colors): JsonResponse
    {
        $allowed = array_keys(self::PALETTE);

        foreach ($colors as $key => $value) {
            if (! is_string($key) || ! in_array($key, $allowed, true) || ! is_string($value) || $value === '') {
                continue;
            }

            $this->model->query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'label' => self::PALETTE[$key][1]]
            );
        }

        return responseHelper(__('Theme updated successfully.'), 200, [
            'colors' => $this->palette(),
        ]);
    }

    private function ensureDefaults(): void
    {
        $this->model->query()
            ->whereIn('key', $this->legacyKeys())
            ->delete();

        foreach (self::PALETTE as $key => [$value, $label]) {
            $this->model->query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'label' => $label]
            );
        }
    }

    private function legacyKeys(): array
    {
        return [
            ...array_map(fn (int $index) => "--theme{$index}", range(2, 9)),
            '--'.'Theme-Color-2',
        ];
    }
}
