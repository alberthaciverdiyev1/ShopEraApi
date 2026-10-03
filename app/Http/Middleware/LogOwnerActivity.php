<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Manager\Entities\SiteOwner;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records what a tenant admin does in the panel (non-GET requests) and keeps
 * the owner's "last seen" fresh, so Manager can monitor activity.
 */
class LogOwnerActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $owner = SiteOwner::findForCurrentTenant();
            if (! $owner) {
                return $response;
            }

            if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                $owner->forceFill(['last_seen_at' => now()])->save();
            } else {
                $route = $request->route()?->getName();
                $owner->touchActivity(
                    $request->method().' '.($route ?: $request->path()),
                    $request->path(),
                    $request->ip(),
                );
            }
        } catch (\Throwable) {
            // Monitoring must never break the request.
        }

        return $response;
    }
}
