<?php

namespace Modules\User\Services;

use Firebase\JWT\JWT;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calls Apple's Sign in with Apple REST API with our own key.
 *
 * Apple asks apps that offer Sign in with Apple to revoke the user's grant when
 * the account is deleted. Revoking takes a refresh token, and the only way to
 * get one is to exchange the one-time authorization code the app receives at
 * sign-in, within five minutes. So the code is exchanged at sign-in and the
 * refresh token kept (encrypted) until the account goes.
 */
class AppleTokenClient
{
    private const TOKEN_URL = 'https://appleid.apple.com/auth/token';

    private const REVOKE_URL = 'https://appleid.apple.com/auth/revoke';

    public function configured(): bool
    {
        $path = (string) config('services.apple.private_key_path');

        return filled(config('services.apple.team_id'))
            && filled(config('services.apple.key_id'))
            && $this->clientId() !== null
            && $path !== ''
            && is_readable($path);
    }

    /**
     * Exchanges a sign-in's authorization code for Apple's refresh token.
     *
     * @return string|null null when the key is not set up or Apple refuses
     */
    public function refreshToken(string $authorizationCode): ?string
    {
        if (! $this->configured()) {
            return null;
        }

        $response = $this->post(self::TOKEN_URL, [
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
        ]);

        $token = $response?->json('refresh_token');

        if (! is_string($token) || $token === '') {
            Log::error('Sign in with Apple: authorization code exchange failed', [
                'status' => $response?->status(),
                'error' => $response?->json('error'),
            ]);

            return null;
        }

        return $token;
    }

    /**
     * Revokes a refresh token. True when Apple accepted the request.
     */
    public function revoke(string $refreshToken): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $response = $this->post(self::REVOKE_URL, [
            'token' => $refreshToken,
            'token_type_hint' => 'refresh_token',
        ]);

        if (! $response?->successful()) {
            Log::error('Sign in with Apple: token revocation failed', [
                'status' => $response?->status(),
                'error' => $response?->json('error'),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Never throws: a failure is logged and comes back as null.
     */
    private function post(string $url, array $fields): ?Response
    {
        try {
            return Http::asForm()->timeout(10)->post($url, [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                ...$fields,
            ]);
        } catch (\Throwable $e) {
            Log::error('Sign in with Apple: request failed', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Apple takes a short-lived JWT signed with our key (ES256) as the client
     * secret.
     */
    private function clientSecret(): string
    {
        $now = time();

        return JWT::encode(
            [
                'iss' => config('services.apple.team_id'),
                'iat' => $now,
                'exp' => $now + 300,
                'aud' => 'https://appleid.apple.com',
                'sub' => $this->clientId(),
            ],
            (string) file_get_contents((string) config('services.apple.private_key_path')),
            'ES256',
            (string) config('services.apple.key_id'),
        );
    }

    /**
     * Native iOS sign-ins are issued for the app's bundle id.
     */
    private function clientId(): ?string
    {
        return config('services.apple.client_ids', [])[0] ?? null;
    }
}
