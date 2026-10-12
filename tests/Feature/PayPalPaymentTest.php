<?php

namespace Tests\Feature;

use App\Enums\OrderStatus as OrderStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderStatus;
use Modules\User\Entities\Address;
use Modules\User\Entities\User;
use Tests\TestCase;

class PayPalPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'app.paypal_client_id' => 'client-id',
            'app.paypal_secret' => 'secret',
            'app.paypal_mode' => 'sandbox',
            'app.paypal_currency' => 'USD',
            'app.paypal_webhook_id' => 'WH-TEST-1',
        ]);
    }

    private function fakePayPal(string $verifyStatus = 'SUCCESS'): void
    {
        Http::fake(function ($request) use ($verifyStatus) {
            $url = $request->url();

            if (str_contains($url, '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'TEST-TOKEN', 'expires_in' => 32400]);
            }

            if (str_contains($url, '/v1/notifications/verify-webhook-signature')) {
                return Http::response(['verification_status' => $verifyStatus]);
            }

            if (str_contains($url, '/capture')) {
                return Http::response([
                    'id' => 'ORDER-1',
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'custom_id' => 'ORDER-TXN',
                        'payments' => ['captures' => [[
                            'id' => 'CAPTURE-1',
                            'status' => 'COMPLETED',
                            'amount' => ['currency_code' => 'USD', 'value' => '25.00'],
                        ]]],
                    ]],
                ]);
            }

            if (str_contains($url, '/v2/checkout/orders')) {
                return Http::response([
                    'id' => 'ORDER-1',
                    'status' => 'CREATED',
                    'links' => [
                        ['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1'],
                    ],
                ]);
            }

            return Http::response([], 404);
        });
    }

    private function makeOrder(float $total = 25.00, float $shipping = 0.00): Order
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'buyer'.uniqid().'@example.com',
            'phone' => '+99450'.random_int(1000000, 9999999),
            'password' => bcrypt('secret123'),
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'city' => 'Baku',
            'town_village_district' => 'Nasimi',
            'street_building_number' => '1',
            'unit_floor_apartment' => '5',
            'full_name' => 'Test Buyer',
            'contact_number' => '+994501112233',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'transaction_id' => 'TXN-'.uniqid(),
            'total_price' => $total,
            'shipping_price' => $shipping,
            'payment_type' => 'CARD',
        ]);

        OrderStatus::create([
            'order_id' => $order->id,
            'status' => OrderStatusEnum::WAITING_PAYMENT->value,
        ]);

        return $order;
    }

    public function test_create_payment_returns_the_paypal_approval_url(): void
    {
        $this->fakePayPal();
        $order = $this->makeOrder();

        $response = $this->postJson('/api/payment/create', ['order_id' => $order->transaction_id]);

        $response->assertStatus(200)
            ->assertJsonPath('redirect_url', 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'paypal_order_id' => 'ORDER-1',
        ]);
    }

    public function test_create_payment_rejects_a_non_payable_order(): void
    {
        $this->fakePayPal();
        $order = $this->makeOrder();

        // Move the order out of WAITING_PAYMENT.
        OrderStatus::create([
            'order_id' => $order->id,
            'status' => OrderStatusEnum::CANCELLED->value,
        ]);

        $this->postJson('/api/payment/create', ['order_id' => $order->transaction_id])
            ->assertStatus(410);
    }

    public function test_success_captures_the_order_and_marks_it_paid(): void
    {
        $this->fakePayPal();
        $order = $this->makeOrder();

        $response = $this->get('/api/payment/success?transaction_id='.$order->transaction_id.'&token=ORDER-1');

        $response->assertStatus(200);

        $order->refresh();
        $this->assertNotNull($order->paid_at);
        $this->assertSame('CAPTURE-1', $order->paypal_capture_id);
    }

    public function test_webhook_completes_a_paid_order(): void
    {
        $this->fakePayPal();
        $order = $this->makeOrder();

        $payload = [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-1',
                'custom_id' => $order->transaction_id,
                'amount' => ['currency_code' => 'USD', 'value' => '25.00'],
                'supplementary_data' => ['related_ids' => ['order_id' => 'ORDER-1']],
            ],
        ];

        $this->postJson('/api/payment/result', $payload)->assertStatus(200);

        $order->refresh();
        $this->assertNotNull($order->paid_at);
        $this->assertSame('CAPTURE-1', $order->paypal_capture_id);
    }

    public function test_webhook_rejects_an_invalid_signature(): void
    {
        $this->fakePayPal('FAILURE');
        $order = $this->makeOrder();

        $payload = [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-1',
                'custom_id' => $order->transaction_id,
                'amount' => ['currency_code' => 'USD', 'value' => '25.00'],
            ],
        ];

        $this->postJson('/api/payment/result', $payload)->assertStatus(403);

        $this->assertNull($order->fresh()->paid_at);
    }
}
