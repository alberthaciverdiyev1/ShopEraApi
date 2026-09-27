<?php

namespace Modules\User\Services;

use Google\Client as GoogleClient;

/**
 * Checks a Google ID token posted by the app.
 *
 * The token has to be signed by Google, unexpired, and issued for one of our
 * own OAuth clients. The audience is the part that matters: without that check
 * an ID token minted for any other app would log its holder in as whatever
 * address it carries.
 */
class GoogleIdTokenVerifier
{
    /**
     * The OAuth clients an ID token may be issued for: the web client of the
     * browser flow plus any client the apps sign in through.
     *
     * @return list<string>
     */
    public function audiences(): array
    {
        return array_values(array_unique(array_filter([
            config('services.google.client_id'),
            config('services.google.app_server_client_id'),
            ...config('services.google.native_client_ids', []),
        ])));
    }

    /**
     * @return array<string, mixed>|null the token's claims, or null when it is
     *                                   not a valid Google ID token for us
     */
    public function verify(string $idToken): ?array
    {
        $audiences = $this->audiences();

        if ($audiences === []) {
            return null;
        }

        try {
            // Deliberately no client id on the client: verifyIdToken() would
            // then accept that one id only, while Android and iOS may sign in
            // through other clients of ours. The whole list is checked below.
            $payload = (new GoogleClient())->verifyIdToken($idToken);
        } catch (\UnexpectedValueException|\InvalidArgumentException|\LogicException $e) {
            // Not a JWT at all, or not one Google could have signed.
            return null;
        }

        if (! is_array($payload) || ! in_array($payload['aud'] ?? null, $audiences, true)) {
            return null;
        }

        return $payload;
    }
}
