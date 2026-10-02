<?php

namespace App\Http\Controllers\Admin;

use App\Support\Features;
use Illuminate\Http\Request;
use Modules\Manager\Entities\SiteOwner;
use Modules\Manager\Services\EntitlementWriter;
use Modules\Manager\Services\ThemeService;
use Modules\Setting\Entities\ThemeColor;

/**
 * The owner picks one of the control-DB theme presets for this site. The
 * selection is stored on the SiteOwner (control DB) and the effective palette
 * is pushed into this tenant's ThemeColor rows so /api/theme keeps working.
 */
class ThemeController extends AdminController
{
    protected string $title = 'Tema';

    public function __construct(private readonly ThemeService $themes, private readonly EntitlementWriter $writer) {}

    public function index(Request $request)
    {
        $owner = $this->owner($request);
        $catalogue = $this->themes->themes();
        $current = $owner?->theme_id;

        $selected = $catalogue->firstWhere('id', $current) ?? $this->themes->defaultTheme();

        return view('admin.pages.theme', [
            'title' => $this->title,
            'themes' => $catalogue->map(fn ($theme) => [
                'id' => $theme->id,
                'name' => $theme->name,
                'slug' => $theme->slug,
                'description' => $theme->description,
                'is_default' => (bool) $theme->is_default,
                'preview' => $this->themes->paletteForTheme($theme),
            ])->all(),
            'current' => $current,
            'error' => null,
            'palette' => $this->themes->paletteForTheme($selected),
            'customThemeEnabled' => Features::enabled('custom_theme', false),
            'localColors' => ThemeColor::query()->orderBy('id')->get(),
        ]);
    }

    /** Owner-specific custom palette (available with the `custom_theme` feature). */
    public function update(Request $request)
    {
        if (! Features::enabled('custom_theme', false)) {
            return back()->withErrors(['theme' => 'Custom tema abunəliyinizə daxil deyil.']);
        }

        $data = $request->validate([
            'colors' => ['required', 'array'],
            'colors.*' => ['required', 'string', 'max:255'],
        ]);

        foreach ($data['colors'] as $key => $value) {
            if (is_string($key) && $value !== '') {
                ThemeColor::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        return back()->with('status', __('Custom tema yadda saxlanıldı.'));
    }

    public function select(Request $request)
    {
        $data = $request->validate([
            'theme_id' => ['required', 'integer', 'exists:control.themes,id'],
        ]);

        $owner = $this->owner($request);

        if (! $owner) {
            return back()->withErrors(['theme' => 'Bu host üçün site sahibi tapılmadı.']);
        }

        $owner->update(['theme_id' => $data['theme_id']]);

        $this->writer->push($owner->fresh('domains'), $request->getHost());

        return back()->with('status', __('Tema seçildi və tətbiq olundu.'));
    }

    /** @return array<string,string> */
    public function currentPalette(): array
    {
        return $this->themes->forOwner($this->owner(request()));
    }

    public function storePalette(array $palette): void
    {
        foreach ($palette as $key => $value) {
            if (is_string($key) && is_string($value) && $value !== '') {
                ThemeColor::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }
    }

    private function owner(Request $request): ?SiteOwner
    {
        return SiteOwner::query()->byHost($request->getHost())->with('theme')->first();
    }
}
