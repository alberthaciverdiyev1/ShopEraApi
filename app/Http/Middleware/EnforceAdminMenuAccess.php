<?php

namespace App\Http\Middleware;

use App\Support\AdminMenu;
use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks direct URL access to menu pages the owner is not entitled to.
 * Mirrors the sidebar filter (feature + plan) so hiding a menu item is not the
 * only protection — the route itself is refused too.
 */
class EnforceAdminMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route()?->getName();

        if ($route !== null) {
            foreach (AdminMenu::groups() as $items) {
                foreach ($items as $item) {
                    if (! $this->matches($route, $item['match'] ?? '')) {
                        continue;
                    }

                    if (isset($item['feature']) && ! Features::enabled($item['feature'])) {
                        abort(403, __('Bu bölmə abunəliyinizə daxil deyil.'));
                    }

                    if (isset($item['plan']) && ! plan($item['plan'])) {
                        abort(403, __('Bu bölmə planınıza daxil deyil.'));
                    }
                }
            }
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
