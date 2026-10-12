<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Service\PayPalService;
use RuntimeException;
use Tests\TestCase;

class PayPalServiceTest extends TestCase
{
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

    private function fakePayPal(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'TEST-TOKEN', 'expires_in' => 32400]);
            }

            if (str_contains($url, '/v1/notifications/verify-webhook-signature')) {
                return Http::response(['verification_status' => 'SUCCESS']);
            }

            if (str_ends_with($url, '/capture')) {
                return Http::response([
                    'id' => 'ORDER-1',
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'custom_id' => 'TXN-1',
                        'payments' => ['captures' => [[
                            'id' => 'CAPTURE-1',
                            'status' => 'COMPLETED',
                            'amount' => ['currency_code' => 'USD', 'value' => '25.00'],
                        ]]],
                    ]],
                ]);
            }

            if (str_contains($url, '/v2/checkout/orders/')) {
                return Http::response([
                    'id' => 'ORDER-1',
                    'status' => 'APPROVED',
                    'purchase_units' => [[
                        'custom_id' => 'TXN-1',
                        'payments' => ['captures' => [[
                            'id' => 'CAPTURE-1',
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
                        ['rel' => 'self', 'href' => 'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER-1'],
                        ['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1'],
                    ],
                ]);
            }

            if (str_contains($url, '/refund')) {
                return Http::response([
                    'id' => 'REFUND-1',
                    'status' => 'COMPLETED',
                    'amount' => ['currency_code' => 'USD', 'value' => '25.00'],
                ]);
            }

            return Http::response([], 404);
        });
    }

    public function test_it_fetches_and_caches_an_access_token(): void
    {
        $this->fakePayPal();

        $service = new PayPalService;

        $this->assertSame('TEST-TOKEN', $service->accessToken());
        $this->assertSame('TEST-TOKEN', $service->accessToken());

        // The token is cached, so only one auth request is made.
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/oauth2/token')
            && $request->hasHeader('Authorization'));
    }

    public function test_it_creates_an_order_and_returns_the_approval_url(): void
    {
        $this->fakePayPal();

        $order = (new PayPalService)->createOrder(
            'TXN-1',
            25.0,
            'USD',
            'Payment for order #TXN-1',
            'https://shop.test/api/payment/success?transaction_id=TXN-1',
            'https://shop.test/api/payment/error?transaction_id=TXN-1'
        );

        $this->assertSame('ORDER-1', $order->id);
        $this->assertSame(
            'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1',
            (new PayPalService)->approveUrl($order)
        );

        Http::assertSent(function ($request) {
            if (! str_ends_with($request->url(), '/v2/checkout/orders')) {
                return false;
            }

            $body = $request->data();

            return $body['intent'] === 'CAPTURE'
                && $body['purchase_units'][0]['custom_id'] === 'TXN-1'
                && $body['purchase_units'][0]['amount']['value'] === '25.00'
                && $body['purchase_units'][0]['amount']['currency_code'] === 'USD';
        });
    }

    public function test_it_captures_an_order(): void
    {
        $this->fakePayPal();

        $capture = (new PayPalService)->captureOrder('ORDER-1');

        $this->assertSame('COMPLETED', $capture->status);
        $this->assertSame('CAPTURE-1', $capture->purchase_units[0]->payments->captures[0]->id);
    }

    public function test_it_refunds_a_capture_with_an_amount(): void
    {
        $this->fakePayPal();

        $refund = (new PayPalService)->refund('CAPTURE-1', 25.0);

        $this->assertSame('COMPLETED', $refund->status);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/v2/payments/captures/CAPTURE-1/refund')) {
                return false;
            }

            return $request->data()['amount']['value'] === '25.00'
                && $request->data()['amount']['currency_code'] === 'USD';
        });
    }

    public function test_it_verifies_a_webhook_signature(): void
    {
        $this->fakePayPal();

        $headers = [
            'paypal-auth-algo' => ['SHA256withRSA'],
            'paypal-cert-url' => ['https://api.paypal.com/cert'],
            'paypal-transmission-id' => ['abc'],
            'paypal-transmission-sig' => ['sig'],
            'paypal-transmission-time' => ['2026-10-12T00:00:00Z'],
        ];

        $this->assertTrue((new PayPalService)->verifyWebhook($headers, ['event_type' => 'PAYMENT.CAPTURE.COMPLETED']));
    }

    public function test_it_throws_when_paypal_returns_an_error(): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'TEST-TOKEN']),
            '*/v2/checkout/orders' => Http::response(['message' => 'Invalid request'], 400),
        ]);

        $this->expectException(RuntimeException::class);

        (new PayPalService)->createOrder('TXN-1', 25.0, 'USD', 'desc', 'https://ok', 'https://no');
    }
}
