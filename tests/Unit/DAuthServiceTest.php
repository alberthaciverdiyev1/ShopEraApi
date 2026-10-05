<?php

namespace Tests\Unit;

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
}
