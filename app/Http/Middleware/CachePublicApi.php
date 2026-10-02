<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds CDN cache headers to public, anonymous GET API endpoints so Cloudflare
 * can serve them from the edge. User-specific and product-listing endpoints
 * are intentionally excluded.
 */
class CachePublicApi
{
    private const EXACT = [
        'api/features',
        'api/theme',
        'api/setting',
        'api/promo-blocks',
        'api/home',
        'api/data-version',
        'api/contact',
        'api/subscription',
    ];

    private const PREFIXES = [
        'api/banner',
        'api/category',
        'api/faq',
        'api/legal-terms',
        'api/review/featured',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && ! $request->user() && $this->cacheable($request->path())) {
            $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage=300, stale-while-revalidate=600');
        }

        return $response;
    }

    private function cacheable(string $path): bool
    {
        $path = ltrim($path, '/');

        if (in_array($path, self::EXACT, true)) {
            return true;
        }

        foreach (self::PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
