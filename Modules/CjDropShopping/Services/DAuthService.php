<?php

namespace Modules\CjDropShopping\Services;

use App\Support\TenantContext;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Setting\Entities\Setting;
use RuntimeException;

/**
 * CJ Dropshipping authentication.
 *
 * Owns the API key and the short-lived access token only. The API key is read
 * from the tenant's `settings` row (editable in the admin panel when the
 * `cj_dropshipping` feature is enabled), falling back to module config/env.
 * The token is cached per tenant/per key, and is consumed by the other D*
 * services through {@see DBaseService}.
 *
 * @see https://developers.cjdropshipping.com
 */
class DAuthService
{
    /** @var array<string,?string> per-request memo of the settings row value */
    private array $storedMemo = [];

    public function __construct(private readonly ?array $config = null) {}

    public function isConfigured(): bool
    {
        return $this->apiKey() !== null;
    }

    /**
     * Verify the API key and return connection metadata (never the secret).
     */
    public function connection(): array
    {
        $this->token();

        $cached = Cache::get($this->tokenCacheKey(), []);
        $expiresAt = $cached['expires_at'] ?? null;

        return [
            'connected' => true,
            'base_url' => $this->setting('base_url'),
            'token_expires_at' => $expiresAt ? Carbon::createFromTimestamp($expiresAt)->toIso8601String() : null,
        ];
    }

    /**
     * Return a valid access token.
     *
     * Reuses the cached token while it is still fresh, tries a token refresh
     * when only the refresh token is still valid, and falls back to a full
     * login (API key) otherwise.
     */
    public function token(): string
    {
        $this->ensureConfigured();

        $cached = Cache::get($this->tokenCacheKey(), []);
        $buffer = (int) $this->setting('token_cache_buffer', 300);

        if (is_array($cached)
            && ! empty($cached['access_token'])
            && ($cached['expires_at'] ?? 0) > now()->addSeconds($buffer)->getTimestamp()) {
            return (string) $cached['access_token'];
        }

        if (is_array($cached)
            && ! empty($cached['refresh_token'])
            && ($cached['refresh_expires_at'] ?? 0) > now()->addSeconds($buffer)->getTimestamp()) {
            try {
                return $this->refresh((string) $cached['refresh_token']);
            } catch (RuntimeException) {
                // Refresh token rejected — fall through to a full login.
            }
        }

        return $this->store($this->authenticate());
    }

    /**
     * Exchange a refresh token for a new access token.
     *
     * @param  string|null  $refreshToken  Defaults to the cached refresh token.
     */
    public function refresh(?string $refreshToken = null): string
    {
        $this->ensureConfigured();

        $refreshToken ??= $this->cached('refresh_token');

        if ($refreshToken === null || $refreshToken === '') {
            throw new RuntimeException(__('CJ Dropshipping has no refresh token to use.'));
        }

        $response = $this->http()->post($this->url('authentication/refreshAccessToken'), [
            'refreshToken' => $refreshToken,
        ]);

        return $this->store($this->unwrap($response));
    }

    /**
     * Invalidate the session server-side and drop the cached tokens. Never
     * throws — logout is best-effort.
     */
    public function logout(): void
    {
        try {
            $token = $this->cached('access_token');

            if ($token !== null) {
                $this->http()
                    ->withHeaders(['CJ-Access-Token' => $token])
                    ->post($this->url('authentication/logout'));
            }
        } catch (\Throwable $e) {
            // Never log secrets; the local token is cleared regardless.
            Log::warning('CJ Dropshipping logout failed', ['message' => $e->getMessage()]);
        } finally {
            $this->forgetToken();
        }
    }

    /** Drop the cached token (e.g. after a rejected request or an API key change). */
    public function forgetToken(): void
    {
        Cache::forget($this->tokenCacheKey());
    }

    /** Full login with the API key. @return array<string,mixed> */
    protected function authenticate(): array
    {
        $response = $this->http()->post($this->url('authentication/getAccessToken'), [
            'apiKey' => $this->apiKey(),
        ]);

        return $this->unwrap($response);
    }

    /**
     * Persist a token payload and return its access token.
     *
     * @param  array<string,mixed>  $data
     */
    protected function store(array $data): string
    {
        if (empty($data['accessToken'])) {
            throw new RuntimeException(__('CJ Dropshipping did not return an access token.'));
        }

        $expiresAt = ! empty($data['accessTokenExpiryDate'])
            ? Carbon::parse($data['accessTokenExpiryDate'])->getTimestamp()
            : now()->addHours(12)->getTimestamp();

        $refreshExpiresAt = ! empty($data['refreshTokenExpiryDate'])
            ? Carbon::parse($data['refreshTokenExpiryDate'])->getTimestamp()
            : null;

        // Keep the entry for as long as either token can still be useful, so a
        // live refresh token is not evicted the moment the access token expires.
        $ttl = max(60, max($expiresAt, $refreshExpiresAt ?? 0) - now()->getTimestamp());

        Cache::put($this->tokenCacheKey(), [
            'access_token' => (string) $data['accessToken'],
            'refresh_token' => $data['refreshToken'] ?? null,
            'expires_at' => $expiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
        ], $ttl);

        return (string) $data['accessToken'];
    }

    protected function cached(string $key): ?string
    {
        $value = Cache::get($this->tokenCacheKey(), [])[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return array<string,mixed> */
    protected function unwrap(Response $response): array
    {
        $body = $this->json($response);
        $code = (int) ($body['code'] ?? 0);

        if ($response->failed() || $code !== 200) {
            $this->fail($response, $body);
        }

        return is_array($body['data'] ?? null) ? $body['data'] : [];
    }

    /** @return array<string,mixed> */
    protected function json(Response $response): array
    {
        $body = $response->json();

        return is_array($body) ? $body : [];
    }

    /** @param array<string,mixed> $body */
    protected function fail(Response $response, array $body): never
    {
        // Never log the API key or full payloads; only the status and CJ code.
        Log::warning('CJ Dropshipping authentication failed', [
            'status' => $response->status(),
            'code' => $body['code'] ?? null,
        ]);

        $message = $body['message'] ?? null;

        throw new RuntimeException(
            is_string($message) && $message !== ''
                ? $message
                : __('CJ Dropshipping API request failed.')
        );
    }

    protected function http(): PendingRequest
    {
        return Http::timeout((int) $this->setting('timeout', 20))->acceptJson();
    }

    protected function url(string $path): string
    {
        return rtrim((string) $this->setting('base_url'), '/').'/'.ltrim($path, '/');
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(__('CJ Dropshipping is not configured. Set CJ_DROPSHIPPING_API_KEY or the admin settings field.'));
        }
    }

    protected function tokenCacheKey(): string
    {
        $fingerprint = sha1((string) $this->apiKey());

        return TenantContext::cacheKey('cjdropshopping:access_token:'.$fingerprint);
    }

    /**
     * Resolve the API key: explicit constructor config (tests/manual wiring)
     * wins, then the tenant's settings row, then module config/env.
     */
    protected function apiKey(): ?string
    {
        if ($this->config !== null) {
            $key = $this->config['api_key'] ?? null;

            return is_string($key) && $key !== '' ? $key : null;
        }

        $stored = $this->storedApiKey();

        if ($stored !== null) {
            return $stored;
        }

        $fallback = config('cjdropshopping.api_key');

        return is_string($fallback) && $fallback !== '' ? $fallback : null;
    }

    /**
     * Read the API key from the tenant's single settings row. Fails soft so an
     * unmigrated/absent table never breaks the integration.
     */
    protected function storedApiKey(): ?string
    {
        if (array_key_exists('api_key', $this->storedMemo)) {
            return $this->storedMemo['api_key'];
        }

        try {
            $value = Setting::query()->first()?->cj_dropshipping_api_key;
        } catch (\Throwable) {
            $value = null;
        }

        return $this->storedMemo['api_key'] = (is_string($value) && $value !== '' ? $value : null);
    }

    /**
     * Non-secret config value (base_url, timeout, token buffer, ...). A partial
     * constructor config overrides the module config rather than replacing it.
     */
    protected function setting(string $key, mixed $default = null): mixed
    {
        $config = array_replace(config('cjdropshopping', []), $this->config ?? []);

        return $config[$key] ?? $default;
    }
}
