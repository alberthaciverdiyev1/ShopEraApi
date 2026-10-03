<?php

namespace App\Http\Middleware;

use App\Support\AdminMenu;
use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the sidebar's feature/plan rules to the routes behind it.
 *
 * A locked section stays reachable but read-only: GET requests render the page
 * with a "Premium required" overlay (menuLocked), while any write (POST/PUT/
 * PATCH/DELETE) is refused with 403 so a free plan cannot change anything.
 */
class EnforceAdminMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route()?->getName();
        $locked = false;
        $label = null;

        if ($route !== null) {
            foreach (AdminMenu::groups() as $items) {
                foreach ($items as $item) {
                    if (! $this->matches($route, $item['match'] ?? '')) {
                        continue;
                    }

                    $missing = (isset($item['feature']) && ! Features::enabled($item['feature']))
                        || (isset($item['plan']) && ! plan($item['plan']));

                    if ($missing) {
                        $locked = true;
                        $label = $item['label'];
                    }
                }
            }
        }

        if ($locked) {
            if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                abort(403, __('Bu bölmə Premium plandadır. Dəyişiklik etmək üçün planı yüksəldin.'));
            }

            View::share('menuLocked', true);
            View::share('menuLockedLabel', $label);
        } else {
            View::share('menuLocked', false);
            View::share('menuLockedLabel', null);
        }

        return $next($request);
    }

    private function matches(string $route, string $match): bool
    {
        if ($match === '') {
            return false;
        }

        $prefix = rtrim(str_replace('*', '', $match), '.');

        return $route === $prefix || str_starts_with($route, $prefix.'.');
    }
}
