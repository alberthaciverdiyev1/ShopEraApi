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
 * Owns the credentials and the short-lived access token only. The e-mail + API
 * key are read from the tenant's `settings` row (editable in the admin panel
 * when the `cj_dropshipping` feature is enabled), falling back to module
 * config/env. The token is cached per tenant and per credential pair, and is
 * consumed by the other D* services through {@see DBaseService}.
 *
 * @see https://developers.cjdropshipping.com
 */
class DAuthService
{
    /** @var array<string,?string> per-request memo of the settings row values */
    private array $storedMemo = [];

    public function __construct(private readonly ?array $config = null) {}

    public function isConfigured(): bool
    {
        return $this->credential('email') !== null
            && $this->credential('email') !== ''
            && $this->credential('api_key') !== null
            && $this->credential('api_key') !== '';
    }

    /**
     * Verify the credentials and return connection metadata (never the secrets).
     */
    public function connection(): array
    {
        $this->token();

        $cached = Cache::get($this->tokenCacheKey(), []);
        $expiresAt = $cached['expires_at'] ?? null;

        return [
            'connected' => true,
            'email' => $this->maskEmail((string) $this->credential('email')),
            'base_url' => $this->setting('base_url'),
            'token_expires_at' => $expiresAt ? Carbon::createFromTimestamp($expiresAt)->toIso8601String() : null,
        ];
    }

    /**
     * Return a valid access token, authenticating (and caching) it when needed.
     */
    public function token(): string
    {
        $this->ensureConfigured();

        $cached = Cache::get($this->tokenCacheKey());

        if (is_array($cached)
            && ! empty($cached['access_token'])
            && ($cached['expires_at'] ?? 0) > now()->addSeconds((int) $this->setting('token_cache_buffer', 300))->getTimestamp()) {
            return (string) $cached['access_token'];
        }

        $data = $this->authenticate();

        if (empty($data['accessToken'])) {
            throw new RuntimeException(__('CJ Dropshipping did not return an access token.'));
        }

        $expiresAt = ! empty($data['accessTokenExpiryDate'])
            ? Carbon::parse($data['accessTokenExpiryDate'])->getTimestamp()
            : now()->addHours(12)->getTimestamp();

        Cache::put($this->tokenCacheKey(), [
            'access_token' => $data['accessToken'],
            'refresh_token' => $data['refreshToken'] ?? null,
            'expires_at' => $expiresAt,
        ], max(60, $expiresAt - now()->getTimestamp()));

        return (string) $data['accessToken'];
    }

    /** Drop the cached token (e.g. after a rejected request or a credential change). */
    public function forgetToken(): void
    {
        Cache::forget($this->tokenCacheKey());
    }

    /** @return array<string,mixed> */
    protected function authenticate(): array
    {
        $response = $this->http()->post($this->url('authentication/getAccessToken'), [
            'email' => $this->credential('email'),
            'password' => $this->credential('api_key'),
        ]);

        return $this->unwrap($response);
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
        // Never log credentials or full payloads; only the status and CJ code.
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
            throw new RuntimeException(__('CJ Dropshipping is not configured. Set CJ_DROPSHIPPING_EMAIL and CJ_DROPSHIPPING_API_KEY.'));
        }
    }

    protected function tokenCacheKey(): string
    {
        $fingerprint = sha1(($this->credential('email') ?? '').'|'.($this->credential('api_key') ?? ''));

        return TenantContext::cacheKey('cjdropshipping:access_token:'.$fingerprint);
    }

    /**
     * Resolve a credential: explicit constructor config (tests/manual wiring)
     * wins, then the tenant's settings row, then module config/env.
     */
    protected function credential(string $key): ?string
    {
        if ($this->config !== null) {
            return $this->config[$key] ?? null;
        }

        $stored = $this->storedCredential($key);

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $fallback = config('cjdropshipping.'.$key);

        return is_string($fallback) && $fallback !== '' ? $fallback : null;
    }

    /**
     * Read the credential from the tenant's single settings row. Fails soft so
     * an unmigrated/absent table never breaks the integration.
     */
    protected function storedCredential(string $key): ?string
    {
        $column = match ($key) {
            'email' => 'cj_dropshipping_email',
            'api_key' => 'cj_dropshipping_api_key',
            default => null,
        };

        if ($column === null) {
            return null;
        }

        if (array_key_exists($key, $this->storedMemo)) {
            return $this->storedMemo[$key];
        }

        try {
            $value = Setting::query()->first()?->{$column};
        } catch (\Throwable) {
            $value = null;
        }

        return $this->storedMemo[$key] = (is_string($value) && $value !== '' ? $value : null);
    }

    /** Non-secret config value (base_url, timeout, token buffer, ...). */
    protected function setting(string $key, mixed $default = null): mixed
    {
        return ($this->config ?? config('cjdropshipping', []))[$key] ?? $default;
    }

    protected function maskEmail(string $email): string
    {
        $at = strpos($email, '@');

        if ($at === false || $at < 2) {
            return $email === '' ? '' : str_repeat('*', strlen($email));
        }

        return substr($email, 0, 2).str_repeat('*', max(1, $at - 2)).substr($email, $at);
    }
}
