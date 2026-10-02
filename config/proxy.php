<?php

return [
    // Which proxies may set X-Forwarded-* headers. Tenant resolution reads the
    // host (via X-Forwarded-Host when a trusted proxy sets it), so in
    // production this must list the real proxy/container IPs or ranges —
    // never leave it as "*" on a public host.
    'trusted' => array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '*')))),
];
