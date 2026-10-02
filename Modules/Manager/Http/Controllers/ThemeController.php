<?php

namespace Modules\Manager\Http\Controllers;

use Modules\Manager\Entities\Theme;
use Modules\Manager\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    public function __construct(private readonly ThemeService $theme) {}

    public function index()
    {
        return view('manager::theme.index', [
            'title' => 'Temalar',
            'themes' => Theme::query()->orderByDesc('is_default')->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return view('manager::theme.form', [
            'title' => 'Yeni tema',
            'theme' => null,
            'labels' => $this->theme->labels(),
            'colors' => collect(ThemeService::DEFAULT_PALETTE)->map(fn ($v) => $v[0])->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMeta($request);

        $theme = $this->theme->createTheme($data, $request->input('colors', []));

        return redirect()->route('manager.themes.edit', $theme)->with('status', __('Tema yaradıldı.'));
    }

    public function edit(Theme $theme)
    {
        $this->theme->ensurePalette($theme);

        return view('manager::theme.form', [
            'title' => 'Tema: '.$theme->name,
            'theme' => $theme,
            'labels' => $this->theme->labels(),
            'colors' => $theme->colors()->pluck('value', 'key')->all(),
        ]);
    }

    public function update(Request $request, Theme $theme)
    {
        $theme->update($this->validateMeta($request, $theme));

        $this->theme->updatePalette($theme, $request->input('colors', []));

        if ($theme->is_default) {
            $this->theme->makeDefault($theme);
        }

        return back()->with('status', __('Tema yeniləndi.'));
    }

    public function destroy(Theme $theme)
    {
        abort_if($theme->is_default, 403, 'Default tema silinə bilməz.');

        $theme->delete();

        return redirect()->route('manager.themes.index')->with('status', __('Tema silindi.'));
    }

    public function makeDefault(Theme $theme)
    {
        $this->theme->makeDefault($theme);

        return back()->with('status', __('Default tema dəyişdirildi.'));
    }

    private function validateMeta(Request $request, ?Theme $theme = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/', 'unique:control.themes,slug'.($theme ? ','.$theme->id : '')],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        return [
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'is_default' => $request->boolean('is_default'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
