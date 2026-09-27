<?php

namespace App\Services\Starex;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StarexClient
{
    private const TOKEN_CACHE_KEY = 'starex.partner-panel.access-token';
    private const USER_AGENT = 'TeymurStore/1.0 (+https://teymurstore.az)';

    public function configured(): bool
    {
        return filled(config('services.starex.email'))
            && filled(config('services.starex.password'));
    }

    public function createPackage(array $payload): array
    {
        return $this->send('post', '/api/v1/partner-panel/packages/create', $payload);
    }

    public function deletePackage(string $trackingNumber): array
    {
        return $this->send('post', '/api/v1/partner-panel/packages/delete', [
            'tracking_number' => $trackingNumber,
        ]);
    }

    public function packageHistory(string $trackingNumber): array
    {
        return $this->send('get', "/api/v1/partner-panel/packages/{$trackingNumber}/history");
    }

    public function pickupPoints(): array
    {
        return $this->send('get', '/api/v1/partner-panel/pudo-list');
    }

    public function cities(): array
    {
        return $this->send('get', '/api/v1/partner-panel/cities');
    }

    public function districts(int $cityId): array
    {
        return $this->send('get', "/api/v1/partner-panel/districts/{$cityId}");
    }

    public function towns(int $districtId): array
    {
        return $this->send('get', "/api/v1/partner-panel/towns/{$districtId}");
    }

    private function send(string $method, string $uri, array $payload = []): array
    {
        if (!$this->configured()) {
            throw new RuntimeException('Starex API credentials are not configured.');
        }

        $response = $this->request($this->accessToken())->{$method}($uri, $payload);

        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $response = $this->request($this->accessToken(true))->{$method}($uri, $payload);
        }

        $response->throw();

        return $response->json() ?? [];
    }

    private function accessToken(bool $forceRefresh = false): string
    {
        if (!$forceRefresh && ($cached = Cache::get(self::TOKEN_CACHE_KEY))) {
            return $cached;
        }

        $response = Http::baseUrl(rtrim((string) config('services.starex.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withUserAgent(self::USER_AGENT)
            ->timeout(20)
            ->post('/api/v1/partner-panel/auth/login', [
                'email' => config('services.starex.email'),
                'password' => config('services.starex.password'),
            ]);

        $response->throw();

        $data = $response->json('data') ?? $response->json();
        $token = $data['access_token'] ?? null;

        if (!$token) {
            throw new RuntimeException('Starex login response did not contain an access token.');
        }

        $expiresAt = (int) ($data['expires_at'] ?? 0);
        $ttl = $expiresAt > time() ? max(60, $expiresAt - time() - 60) : 3600;
        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($ttl));

        return $token;
    }

    private function request(string $token): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.starex.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withUserAgent(self::USER_AGENT)
            ->withToken($token)
            ->timeout(20);
    }
}
