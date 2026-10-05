<?php

namespace Modules\CjDropShopping\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Shared plumbing for the authenticated CJ Dropshipping calls.
 *
 * Domain services (products, categories, freight, ...) extend this and stay
 * focused on their own endpoints; token handling and envelope/retry logic live
 * here and are delegated to {@see DAuthService}.
 */
abstract class DBaseService
{
    /** CJ error codes that mean "the access token is invalid/expired". */
    private const TOKEN_ERROR_CODES = [1600200, 1600300];

    public function __construct(protected readonly DAuthService $auth) {}

    /**
     * Authenticated GET returning the unwrapped CJ `data` payload.
     *
     * @return array<string,mixed>
     */
    protected function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    /**
     * Authenticated POST (JSON) returning the unwrapped CJ `data` payload.
     *
     * @return array<string,mixed>
     */
    protected function post(string $path, array $payload = []): array
    {
        return $this->send('POST', $path, $payload);
    }

    /**
     * Send a request and unwrap the CJ envelope. A single retry is attempted
     * with a fresh token when CJ reports the token as invalid.
     *
     * @return array<string,mixed>
     */
    protected function send(string $method, string $path, array $payload = [], bool $retry = true): array
    {
        $client = $this->http()->withHeaders(['CJ-Access-Token' => $this->auth->token()]);
        $url = $this->url($path);

        $response = $method === 'GET'
            ? $client->get($url, $payload)
            : $client->post($url, $payload);

        $body = $this->json($response);
        $code = (int) ($body['code'] ?? 0);

        if ($response->failed() || $code !== 200) {
            if ($retry && in_array($code, self::TOKEN_ERROR_CODES, true)) {
                $this->auth->forgetToken();
                $this->auth->token();

                return $this->send($method, $path, $payload, false);
            }

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
        Log::warning('CJ Dropshipping API request failed', [
            'status' => $response->status(),
            'code' => $body['code'] ?? null,
            'path' => $response->effectiveUri()?->getPath(),
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
        return Http::timeout((int) config('cjdropshopping.timeout', 20))->acceptJson();
    }

    protected function url(string $path): string
    {
        return rtrim((string) config('cjdropshopping.base_url'), '/').'/'.ltrim($path, '/');
    }
}
