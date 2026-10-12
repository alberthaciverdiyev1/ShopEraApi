<?php

namespace Modules\Payment\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the PayPal REST API (v2 Checkout Orders flow).
 *
 * Only the pieces Snaker needs are implemented:
 *   - OAuth2 client_credentials token
 *   - create order   (POST /v2/checkout/orders)
 *   - capture order  (POST /v2/checkout/orders/{id}/capture)
 *   - get order      (GET  /v2/checkout/orders/{id})
 *
 * Saved cards / vault are intentionally NOT supported.
 */
class PayPalService
{
    private string $clientId;

    private string $secret;

    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = (string) config('app.paypal_client_id');
        $this->secret = (string) config('app.paypal_secret');
        $this->baseUrl = config('app.paypal_mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Get (and cache) an OAuth2 access token. Tokens are valid for ~9 hours;
     * we cache for 50 minutes to be safe.
     */
    public function accessToken(): string
    {
        return Cache::remember('paypal_access_token', now()->addMinutes(50), function () {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->secret)
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed() || ! $response->json('access_token')) {
                throw new RuntimeException('PayPal authentication failed: '.$response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * Create a PayPal order and return the raw API response.
     */
    public function createOrder(
        string $orderId,
        float $amount,
        string $currency,
        string $description,
        string $returnUrl,
        string $cancelUrl
    ): object {
        $response = $this->request()->post("{$this->baseUrl}/v2/checkout/orders", [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $orderId,
                'description' => $description,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'brand_name' => config('app.name'),
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ]);

        return $this->decode($response, 'create order');
    }

    /**
     * Capture a previously approved PayPal order.
     */
    public function captureOrder(string $paypalOrderId): object
    {
        $response = $this->request()->post("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}/capture");

        return $this->decode($response, 'capture order');
    }

    /**
     * Fetch a PayPal order (used for status checks).
     */
    public function getOrder(string $paypalOrderId): object
    {
        $response = $this->request()->get("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}");

        return $this->decode($response, 'get order');
    }

    /**
     * Extract the customer approval / redirect link from a create-order response.
     */
    public function approveUrl(object $order): ?string
    {
        foreach ($order->links ?? [] as $link) {
            if (($link->rel ?? null) === 'approve') {
                return $link->href ?? null;
            }
        }

        return null;
    }

    private function request()
    {
        return Http::withToken($this->accessToken())
            ->withHeaders(['Content-Type' => 'application/json'])
            ->acceptJson();
    }

    private function decode($response, string $action): object
    {
        $payload = json_decode($response->body());

        if ($response->failed()) {
            $message = $payload->message ?? $response->body();

            throw new RuntimeException("PayPal {$action} failed: {$message}");
        }

        return $payload ?? (object) [];
    }
}
