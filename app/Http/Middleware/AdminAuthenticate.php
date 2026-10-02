<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every /admin route. Uses the dedicated "admin" session guard so the
 * marketplace users table backs the panel without touching the API's sanctum
 * tokens. htmx requests get an HX-Redirect so the browser navigates instead of
 * swapping an empty fragment into the page.
 */
class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            if ($request->header('HX-Request')) {
                return response('', 401)->header('HX-Redirect', route('admin.login'));
            }

            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
