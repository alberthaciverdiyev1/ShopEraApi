<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\Setting\Entities\ThemeColor;

/**
 * The storefront colour palette, stored in this application's own
 * `theme_colors` table (no control plane).
 */
class ThemeController extends AdminController
{
    protected string $title = 'Tema';

    public function index()
    {
        return view('admin.pages.theme', [
            'title' => $this->title,
            'localColors' => ThemeColor::query()->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'colors' => ['required', 'array'],
            'colors.*' => ['required', 'string', 'max:255'],
        ]);

        foreach ($data['colors'] as $key => $value) {
            if (is_string($key) && $value !== '') {
                ThemeColor::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        return back()->with('status', __('Tema yadda saxlanıldı.'));
    }
}
