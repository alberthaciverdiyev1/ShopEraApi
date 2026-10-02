<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level feature gate: `->middleware('feature:chat')` returns 404 when the
 * owner's subscription does not include that feature.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature, bool $default = true): Response
    {
        if (! Features::enabled($feature, $default)) {
            abort(404);
        }

        return $next($request);
    }
}
