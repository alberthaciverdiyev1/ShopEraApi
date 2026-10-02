<?php

namespace Tests\Feature;

use App\Jobs\Middleware\RestoreTenantContext;
use App\Jobs\SendUserPushJob;
use App\Support\TenantDatabase;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    public function test_database_name_is_derived_consistently(): void
    {
        $this->assertSame('shopera_redbull_shopera_test', TenantDatabase::nameFor('redbull.shopera.test'));
        $this->assertSame('shopera_mystore_com', TenantDatabase::nameFor('mystore.com'));
        $this->assertSame(
            TenantDatabase::nameFor('mystore.com'),
            TenantDatabase::nameFor('MyStore.COM')
        );
    }

    public function test_databases_collapses_multiple_domains_to_one_database(): void
    {
        TenantDatabase::cache()->put(config('tenant.map_cache'), [
            'store.shopera.test' => 'shopera_store',
            'store.example.com' => 'shopera_store',
            'other.shopera.test' => 'shopera_other',
        ]);
        TenantDatabase::cache()->put(config('tenant.metadata_cache'), [
            'store.example.com' => ['storage_root' => 'store'],
        ]);

        $this->assertSame([
            'shopera_store' => 'store',
            'shopera_other' => null,
        ], TenantDatabase::databases());
    }

    public function test_each_runs_once_per_database(): void
    {
        TenantDatabase::cache()->put(config('tenant.map_cache'), [
            'a.test' => 'shopera_a',
            'b.test' => 'shopera_a',
            'c.test' => 'shopera_c',
        ]);

        $seen = [];
        TenantDatabase::each(function (?string $database) use (&$seen) {
            $seen[] = $database;
        });

        $this->assertSame(['shopera_a', 'shopera_c'], $seen);
    }

    public function test_each_falls_back_to_central_when_no_tenants(): void
    {
        TenantDatabase::cache()->forget(config('tenant.map_cache'));

        $seen = ['unset'];
        TenantDatabase::each(function (?string $database) use (&$seen) {
            $seen = [$database];
        });

        $this->assertSame([null], $seen);
    }

    public function test_unknown_host_is_not_guessed_when_map_exists(): void
    {
        config(['tenant.enabled' => true]);
        TenantDatabase::cache()->put(config('tenant.map_cache'), ['known.test' => 'shopera_known']);

        $this->getJson('http://unknown.test/api/features')->assertStatus(404);
    }

    public function test_central_webhook_path_never_needs_a_tenant(): void
    {
        config(['tenant.enabled' => true]);
        TenantDatabase::cache()->put(config('tenant.map_cache'), ['known.test' => 'shopera_known']);

        // No tenant matches unknown.test, but the manager webhook is a central
        // route, so it must reach the controller (which then rejects the
        // unconfigured secret) instead of a tenancy 404.
        $this->postJson('http://unknown.test/api/manager/webhook')
            ->assertStatus(500);
    }

    public function test_no_tenants_configured_falls_back_to_central_outside_production(): void
    {
        config(['tenant.enabled' => true]);
        TenantDatabase::cache()->forget(config('tenant.map_cache'));

        $this->getJson('http://localhost/api/features')->assertStatus(200);
    }

    public function test_production_stays_strict_even_with_an_empty_map(): void
    {
        config(['tenant.enabled' => true]);
        TenantDatabase::cache()->forget(config('tenant.map_cache'));

        $previous = app()['env'];
        app()['env'] = 'production';

        try {
            $this->getJson('http://localhost/api/features')->assertStatus(404);
        } finally {
            app()['env'] = $previous;
        }
    }

    public function test_unique_hosts_returns_one_host_per_database(): void
    {
        TenantDatabase::cache()->put(config('tenant.map_cache'), [
            'store.shopera.test' => 'shopera_store',
            'store.example.com' => 'shopera_store',
            'other.shopera.test' => 'shopera_other',
        ]);

        $this->assertSame([
            'store.shopera.test' => 'shopera_store',
            'other.shopera.test' => 'shopera_other',
        ], TenantDatabase::uniqueHosts());
    }

    public function test_tenant_aware_job_captures_the_active_tenant(): void
    {
        config([
            'tenant.current_host' => 'store.test',
            'tenant.current_database' => 'shopera_store',
            'tenant.current_storage_root' => 'store',
        ]);

        $job = new SendUserPushJob(1, 'title', 'body');

        $this->assertSame('store.test', $job->tenantHost);
        $this->assertSame('shopera_store', $job->tenantDatabase);
        $this->assertSame('store', $job->tenantStorageRoot);
    }

    public function test_job_middleware_restores_then_resets_the_tenant_connection(): void
    {
        $job = new \stdClass;
        $job->tenantHost = 'store.test';
        $job->tenantDatabase = 'shopera_store';
        $job->tenantStorageRoot = 'store';

        $inside = [];
        (new RestoreTenantContext)->handle($job, function () use (&$inside) {
            $inside = [
                'connection' => config('database.default'),
                'database' => config('tenant.current_database'),
                'storage' => config('tenant.current_storage_root'),
            ];

            return 'ran';
        });

        $this->assertSame('tenant', $inside['connection']);
        $this->assertSame('shopera_store', $inside['database']);
        $this->assertSame('store', $inside['storage']);

        // The worker's default connection must be put back afterwards.
        $this->assertNotSame('tenant', config('database.default'));
        $this->assertNull(config('tenant.current_database'));
        $this->assertNull(config('tenant.current_storage_root'));
    }
}
