<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Small client for the Manager.Snaker control-plane.
 * Prefers the per-instance bearer token; falls back to the legacy shared key
 * (with an explicit ?host= so one shared instance can resolve per-owner data).
 */
class ManagerClient
{
    public static function get(string $path, array $query = [], ?string $host = null): ?Response
    {
        return self::call('get', $path, $query, $host);
    }

    public static function put(string $path, array $data = [], ?string $host = null): ?Response
    {
        return self::call('put', $path, $data, $host);
    }

    public static function post(string $path, array $data = [], ?string $host = null): ?Response
    {
        return self::call('post', $path, $data, $host);
    }

    private static function call(string $method, string $path, array $payload, ?string $host = null): ?Response
    {
        $config = config('services.manager');

        if (empty($config['url'])) {
            return null;
        }

        $url = rtrim($config['url'], '/').'/'.ltrim($path, '/');
        $headers = [];
        $query = [];

        // Prefer the active tenant's host: one instance serves many tenants and
        // Manager resolves the owner from ?host=. Fall back to the configured
        // single-host value only when there is no tenant context (e.g. CLI).
        $host ??= TenantContext::host() ?: ($config['site_host'] ?? null);

        if (! empty($config['token'])) {
            $headers['Authorization'] = 'Bearer '.$config['token'];
        } else {
            $headers['X-Api-Key'] = (string) ($config['api_key'] ?? '');
        }

        if (! empty($host)) {
            $query['host'] = $host;
        }

        if ($query !== []) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator.http_build_query($query);
        }

        try {
            $request = Http::withHeaders($headers)->timeout(8)->retry(2, 200);

            return match ($method) {
                'put' => $request->put($url, $payload),
                'post' => $request->post($url, $payload),
                default => $payload === [] ? $request->get($url) : $request->get($url, $payload),
            };
        } catch (\Throwable) {
            return null;
        }
    }
}
