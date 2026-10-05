<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Modules\CjDropShopping\Services\DAuthService;
use RuntimeException;
use Tests\TestCase;

class DAuthServiceTest extends TestCase
{
    public function test_it_is_not_configured_without_credentials(): void
    {
        $service = new DAuthService(['email' => '', 'api_key' => null]);

        $this->assertFalse($service->isConfigured());
    }

    public function test_it_is_configured_when_email_and_api_key_are_present(): void
    {
        $service = new DAuthService([
            'email' => 'owner@example.com',
            'api_key' => 'secret-key',
        ]);

        $this->assertTrue($service->isConfigured());
    }

    public function test_connection_fails_when_not_configured(): void
    {
        $service = new DAuthService(['email' => null, 'api_key' => null]);

        $this->expectException(RuntimeException::class);

        $service->connection();
    }

    public function test_it_authenticates_and_reuses_the_cached_token(): void
    {
        Http::fake([
            '*/authentication/getAccessToken' => Http::response($this->payload('AT')),
        ]);

        $service = $this->service();

        $this->assertSame('AT', $service->token());
        $this->assertSame('AT', $service->token());

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/getAccessToken')
            && $request['email'] === 'owner@example.com'
            && $request['password'] === 'secret-key');
    }

    public function test_it_refreshes_the_token_when_the_access_token_expired(): void
    {
        Http::fake([
            '*/authentication/getAccessToken' => Http::response($this->payload('AT-expired', now()->subMinute())),
            '*/authentication/refreshAccessToken' => Http::response($this->payload('AT-new')),
        ]);

        $service = $this->service();

        $this->assertSame('AT-expired', $service->token());
        $this->assertSame('AT-new', $service->token());

        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/refreshAccessToken')
            && $request['refreshToken'] === 'RT');
    }

    public function test_logout_calls_the_endpoint_and_drops_the_cached_token(): void
    {
        Http::fake([
            '*/authentication/getAccessToken' => Http::response($this->payload('AT')),
            '*/authentication/logout' => Http::response(['code' => 200, 'data' => []]),
        ]);

        $service = $this->service();
        $service->token();
        $service->logout();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/logout')
            && $request->hasHeader('CJ-Access-Token', 'AT'));

        // The cache was dropped, so the next call authenticates again.
        $service->token();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/getAccessToken'), 2);
    }

    private function service(): DAuthService
    {
        return new DAuthService([
            'email' => 'owner@example.com',
            'api_key' => 'secret-key',
            'base_url' => 'https://developers.cjdropshipping.com/api2.0/v1',
        ]);
    }

    private function payload(string $accessToken, ?Carbon $expiresAt = null): array
    {
        return [
            'code' => 200,
            'result' => true,
            'data' => [
                'accessToken' => $accessToken,
                'accessTokenExpiryDate' => ($expiresAt ?? now()->addHour())->toIso8601String(),
                'refreshToken' => 'RT',
                'refreshTokenExpiryDate' => now()->addDays(7)->toIso8601String(),
            ],
        ];
    }
}
