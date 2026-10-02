<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Support\ManagerClient;
use Modules\Setting\Entities\ThemeColor;

/**
 * Theme is owned by Manager.Snaker: the instance lists the available themes
 * and asks the Manager to switch. The selected palette is mirrored locally so
 * the storefront's own /api/theme keeps working.
 */
class ThemeController extends AdminController
{
    protected string $title = 'Tema';

    public function index(Request $request)
    {
        [$themes, $current, $error] = $this->fetchThemes($request);

        return view('admin.pages.theme', [
            'title' => $this->title,
            'themes' => $themes,
            'current' => $current,
            'error' => $error,
            'palette' => $themes[$this->indexOfCurrent($themes, $current)]['preview'] ?? [],
            'customThemeEnabled' => $this->customThemeEnabled($request),
            'localColors' => ThemeColor::query()->orderBy('id')->get(),
        ]);
    }

    /** Owner-specific custom palette (available with the `custom_theme` feature). */
    public function update(Request $request)
    {
        if (! $this->customThemeEnabled($request)) {
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

    private function customThemeEnabled(Request $request): bool
    {
        $response = ManagerClient::get('/api/v1/entitlements');
        $value = $response && $response->ok() ? ($response->json('data.features.custom_theme.value') ?? null) : null;

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public function select(Request $request)
    {
        $data = $request->validate([
            'theme_id' => ['required', 'integer'],
        ]);

        $response = ManagerClient::put('/api/v1/theme-selection', [
            'theme_id' => $data['theme_id'],
        ]);

        if (! $response) {
            return back()->withErrors(['theme' => 'Manager əlçatmazdır.']);
        }

        if (! $response->ok()) {
            return back()->withErrors(['theme' => 'Tema seçilə bilmədi ('.$response->status().').']);
        }

        // Mirror the palette the Manager now reports for this instance.
        $palette = $this->currentPalette();
        $this->storePalette($palette);

        return back()->with('status', __('Tema seçildi və tətbiq olundu.'));
    }

    private function fetchThemes(Request $request): array
    {
        if (empty(config('services.manager.url'))) {
            return [[], null, 'Manager konfiqurasiya olunmayıb (MANAGER_URL).'];
        }

        $response = ManagerClient::get('/api/v1/themes');

        if (! $response) {
            return [[], null, 'Manager əlçatmazdır.'];
        }

        if (! $response->ok()) {
            return [[], null, 'Manager cavabı: '.$response->status()];
        }

        return [$response->json('data.themes') ?? [], $response->json('data.current'), null];
    }

    /** @return array<string,string> */
    public function currentPalette(): array
    {
        $response = ManagerClient::get('/api/v1/entitlements');

        if (! $response || ! $response->ok()) {
            return [];
        }

        return $response->json('data.theme') ?? [];
    }

    public function storePalette(array $palette): void
    {
        foreach ($palette as $key => $value) {
            if (is_string($key) && is_string($value) && $value !== '') {
                ThemeColor::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }
    }

    private function indexOfCurrent(array $themes, $current): int
    {
        foreach ($themes as $i => $theme) {
            if ((int) ($theme['id'] ?? 0) === (int) $current) {
                return $i;
            }
        }

        return -1;
    }

    private function siteHost(Request $request): string
    {
        return (string) (config('services.manager.site_host') ?: $request->getHost());
    }
}
