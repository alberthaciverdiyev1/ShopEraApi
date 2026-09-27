<?php

namespace Modules\User\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Checks a Sign in with Apple identity token posted by the app.
 *
 * The token has to be signed with one of Apple's published keys, issued by
 * Apple, unexpired, meant for one of our bundle ids, and carry the nonce the
 * app generated for this very sign-in, so a token taken from elsewhere cannot
 * be replayed.
 */
class AppleIdTokenVerifier
{
    private const ISSUER = 'https://appleid.apple.com';

    private const KEYS_URL = 'https://appleid.apple.com/auth/keys';

    private const KEYS_CACHE = 'apple_sign_in_keys';

    /**
     * @return list<string>
     */
    public function audiences(): array
    {
        return array_values(array_filter(config('services.apple.client_ids', [])));
    }

    /**
     * @return array<string, mixed>|null the token's claims, or null when it is
     *                                   not a valid Apple identity token for us
     */
    public function verify(string $identityToken, string $rawNonce): ?array
    {
        $audiences = $this->audiences();

        if ($audiences === [] || $rawNonce === '') {
            return null;
        }

        $payload = $this->decode($identityToken, false);

        // Apple rotates its keys, so an unknown key id means ours may be stale.
        // Refetched at most every five minutes: junk tokens must not turn this
        // endpoint into a way of hammering Apple.
        if ($payload === false && Cache::add(self::KEYS_CACHE.':refetched', true, 300)) {
            Cache::forget(self::KEYS_CACHE);
            $payload = $this->decode($identityToken, true);
        }

        if (! is_object($payload)) {
            return null;
        }

        $claims = (array) $payload;

        if (($claims['iss'] ?? null) !== self::ISSUER
            || ! in_array($claims['aud'] ?? null, $audiences, true)
            || ! is_string($claims['sub'] ?? null)
            || $claims['sub'] === '') {
            return null;
        }

        // The app hands Apple the SHA-256 of a random nonce and sends us the
        // nonce itself.
        if (! hash_equals(hash('sha256', $rawNonce), (string) ($claims['nonce'] ?? ''))) {
            return null;
        }

        return $claims;
    }

    /**
     * @return object|false|null the claims; false when the token's key is not
     *                           among the cached keys; null when it is invalid
     */
    private function decode(string $token, bool $afterRefetch): object|false|null
    {
        try {
            return JWT::decode($token, JWK::parseKeySet($this->keys(), 'RS256'));
        } catch (\UnexpectedValueException $e) {
            // Also a bad signature or an expired token, which are final.
            return ! $afterRefetch && str_contains($e->getMessage(), '"kid"') ? false : null;
        } catch (\DomainException|\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @return array{keys: list<array<string, string>>}
     */
    private function keys(): array
    {
        return Cache::remember(self::KEYS_CACHE, now()->addHours(12), fn () => Http::timeout(10)
            ->get(self::KEYS_URL)
            ->throw()
            ->json());
    }
}
