<?php

namespace App\Jobs\Concerns;

/**
 * Single-database deployment: a queued job no longer has to restore a tenant
 * connection, so this trait is a no-op kept for backwards compatibility with
 * jobs that still call captureTenant().
 */
trait TenantAware
{
    public ?string $tenantHost = null;

    public ?string $tenantDatabase = null;

    public ?string $tenantStorageRoot = null;

    protected function captureTenant(): void
    {
        // no-op
    }

    /** @return array<int,object> */
    public function middleware(): array
    {
        return [];
    }
}
