<?php

namespace App\Http\Middleware;

use App\Support\Subscription;
use Closure;
use Illuminate\Http\Request;

/**
 * When the subscription is not usable the panel becomes read-only: only safe
 * (GET) requests pass, every write is bounced back with a message.
 */
class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Subscription::isBlocked() && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return back()->withErrors(['subscription' => 'Abunə aktiv deyil — yalnız baxış rejimi.']);
        }

        return $next($request);
    }
}
