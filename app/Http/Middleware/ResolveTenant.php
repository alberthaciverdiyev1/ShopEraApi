<?php

namespace App\Http\Middleware;

use App\Support\TenantDatabase;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Picks the tenant database for the incoming host. One codebase serves every
 * site owner (free and paid); the host → database map comes from Manager
 * (manager:sync). Every tenant has its own database.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('tenant.enabled')) {
            return $next($request);
        }

        $host = $request->getHost();
        $database = $this->databaseFor($host);

        if ($database !== null && TenantDatabase::exists($database)) {
            $metadata = $this->metadataFor($host);
            config(['database.connections.tenant.database' => $database]);
            config([
                'tenant.current_host' => $host,
                'tenant.current_database' => $database,
                'tenant.current_storage_root' => $metadata['storage_root'] ?? null,
            ]);
            DB::purge('tenant');
            DB::setDefaultConnection('tenant');

            return $next($request);
        }

        if ($this->isCentralRoute($request)) {
            config([
                'tenant.current_host' => null,
                'tenant.current_database' => null,
                'tenant.current_storage_root' => null,
            ]);

            return $next($request);
        }

        // Bootstrap / local development: an instance that has no tenants in its
        // map yet (before the first manager:sync) serves the central database.
        // In production this stays strict, so an expired/empty map can never
        // silently cross tenant boundaries.
        if ($this->mapIsEmpty() && ! app()->environment('production')) {
            config([
                'tenant.current_host' => null,
                'tenant.current_database' => null,
                'tenant.current_storage_root' => null,
            ]);

            return $next($request);
        }

        throw new NotFoundHttpException("Tenant not found for host {$host}.");
    }

    private function mapIsEmpty(): bool
    {
        return (array) TenantDatabase::cache()->get(config('tenant.map_cache'), []) === [];
    }

    private function metadataFor(string $host): array
    {
        $metadata = (array) TenantDatabase::cache()->get(config('tenant.metadata_cache'), []);

        return is_array($metadata[$host] ?? null) ? $metadata[$host] : [];
    }

    private function databaseFor(string $host): ?string
    {
        $map = (array) TenantDatabase::cache()->get(config('tenant.map_cache'), []);

        if ($map !== []) {
            // The map is authoritative: a known tenant resolves, an unknown
            // custom domain must NOT silently guess another tenant's database.
            return $map[$host] ?? null;
        }

        // Bootstrap fallback before the first manager:sync (single host).
        return TenantDatabase::nameFor($host);
    }

    private function isCentralRoute(Request $request): bool
    {
        foreach ((array) config('tenant.central_paths', []) as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }
}
