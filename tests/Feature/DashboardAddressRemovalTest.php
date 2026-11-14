<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\User\Entities\User;
use Tests\TestCase;

class DashboardAddressRemovalTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::query()->create([
            'name' => 'Test',
            'surname' => 'User',
            'email' => 'customer@example.com',
            'phone' => '0500000001',
            'password' => bcrypt('secret123'),
        ]);
    }

    public function test_the_dashboard_no_longer_renders_the_address_list_or_form(): void
    {
        $html = $this->actingAs($this->customer())->get('/dashboard');

        $html->assertOk();
        $html->assertDontSee('data-sf-dash-pane="addresses"', false);
        $html->assertDontSee('Ünvanlar');
        $html->assertDontSee('Yeni ünvan');
        $html->assertDontSee('Ünvanı yadda saxla');
    }

    public function test_the_dashboard_opens_on_the_settings_pane_without_an_overview_tab(): void
    {
        $html = $this->actingAs($this->customer())->get('/dashboard');

        $html->assertOk();
        $html->assertDontSee('Hesab paneli');
        $html->assertDontSee('Son sifarişlər');
        $html->assertSee('data-sf-dash-pane="settings"', false);
        $html->assertSee('Hesab parametrləri');
    }

    public function test_the_address_backend_routes_stay_available(): void
    {
        $this->assertTrue(Route::has('storefront.addresses.store'));
        $this->assertTrue(Route::has('storefront.addresses.update'));
        $this->assertTrue(Route::has('storefront.addresses.destroy'));
    }
}
