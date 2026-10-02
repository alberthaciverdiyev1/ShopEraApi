<?php

namespace Modules\Manager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only the `owner` role may use the manager panel. Any other authenticated
 * account (or a non-owner) is rejected.
 */
class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('owner');

        if (! $user || ! $user->hasRole('owner')) {
            abort(403, 'Bu panelə yalnız owner rolu olan hesab daxil ola bilər.');
        }

        return $next($request);
    }
}
