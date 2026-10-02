<?php

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\RestoreTenantContext;
use App\Support\TenantContext;

/**
 * Captures the active tenant when a job is dispatched (inside the request that
 * still has the tenant connection) so the worker — which has no request host —
 * can restore the same tenant database before the job runs.
 *
 * Call captureTenant() at the end of the job's constructor.
 */
trait TenantAware
{
    public ?string $tenantHost = null;

    public ?string $tenantDatabase = null;

    public ?string $tenantStorageRoot = null;

    protected function captureTenant(): void
    {
        $this->tenantHost = TenantContext::host();
        $this->tenantDatabase = TenantContext::database();
        $this->tenantStorageRoot = config('tenant.current_storage_root');
    }

    /** @return array<int,object> */
    public function middleware(): array
    {
        return [new RestoreTenantContext];
    }
}
