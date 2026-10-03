<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Shared behaviour for every admin page: the sidebar title, permission checks
 * and the small helpers used to answer htmx partials.
 */
abstract class AdminController extends Controller
{
    protected string $title = 'Panel';

    /** Lazily computed so it never depends on a subclass's constructor. */
    private ?array $enabledLocales = null;

    /**
     * Locales this site has enabled (feature flags lang_az/lang_en/lang_ru/lang_tr).
     * Defaults to all four when no flags are synced.
     */
    protected function enabledLocales(): array
    {
        if ($this->enabledLocales === null) {
            // Without the multi_language feature the panel is az-only.
            if (! feature('multi_language')) {
                return $this->enabledLocales = ['az'];
            }

            $this->enabledLocales = array_values(array_filter(
                ['az', 'en', 'ru', 'tr'],
                fn (string $locale): bool => feature("lang_{$locale}")
            ));

            if ($this->enabledLocales === []) {
                $this->enabledLocales = ['az', 'en', 'ru', 'tr'];
            }
        }

        return $this->enabledLocales;
    }

    protected function requirePermission(string $permission): void
    {
        abort_unless(admin_can($permission), 403, __('Bu əməliyyat üçün icazəniz yoxdur.'));
    }

    protected function isHtmx(Request $request): bool
    {
        return $request->header('HX-Request') !== null;
    }

    /**
     * Tell htmx to close the modal / show a toast after swapping the table.
     */
    protected function htmxTriggers(array $events): string
    {
        return json_encode($events, JSON_THROW_ON_ERROR);
    }
}
