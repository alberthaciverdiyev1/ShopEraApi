<?php

return [
    // When enabled, the active database is chosen per request host (a separate
    // database per site owner, one codebase). On by default so a production
    // host never silently falls back to the central database; tests disable it.
    'enabled' => env('TENANCY_ENABLED', true),

    'connection' => 'tenant',

    // Cache store for the host => database map and database-existence flags.
    // Must NOT be tied to the per-tenant database connection: resolved before
    // the connection is switched. "file" and "redis" are safe; "database" is not.
    'cache_store' => env('TENANCY_CACHE_STORE', 'file'),

    // Cache key holding the host => database map pulled from Manager.
    'map_cache' => 'tenant_map',

    // Cache key holding full host metadata from Manager, including storage root.
    'metadata_cache' => 'tenant_metadata',

    // Central (non-tenant) routes that must keep working when no tenant
    // database matches the incoming host (e.g. the signed Manager webhook).
    'central_paths' => ['up'],

    // Hosts that serve the manager (SaaS operator) panel. Requests to these
    // hosts bypass tenant resolution and use the `control` connection.
    'control_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MANAGER_HOSTS', 'manager.snaker.store'))
    ))),
];
