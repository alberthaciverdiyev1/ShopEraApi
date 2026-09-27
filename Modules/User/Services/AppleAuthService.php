<?php

namespace Modules\User\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class AppleAuthService
{
    public function __construct(
        private readonly AppleIdTokenVerifier $verifier,
        private readonly SocialAccountService $accounts,
        private readonly AppleTokenClient $appleTokens,
    ) {
    }

    /**
     * Sign in with Apple from the iOS app: the identity token Apple issued, the
     * raw nonce whose hash the app gave Apple, the one-time authorization code,
     * and, on the first authorisation only, the name the user chose to share.
     */
    public function loginWithToken(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'nonce' => 'required|string|max:255',
            'authorization_code' => 'nullable|string|max:2048',
            'given_name' => 'nullable|string|max:255',
            'family_name' => 'nullable|string|max:255',
        ]);

        if ($this->verifier->audiences() === []) {
            return responseHelper('Apple login is not configured.', StatusCode::HTTP_SERVICE_UNAVAILABLE);
        }

        $claims = $this->verifier->verify($validated['token'], $validated['nonce']);

        if (! $claims) {
            return responseHelper('Invalid Apple ID Token', StatusCode::HTTP_FORBIDDEN);
        }

        // The address is the real one or a private relay, both verified by
        // Apple. Some managed Apple IDs have none, and then only the Apple
        // user id links the account.
        $email = filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && is_string($claims['email'] ?? null)
            ? $claims['email']
            : null;

        $name = trim(implode(' ', array_filter([
            $validated['given_name'] ?? null,
            $validated['family_name'] ?? null,
        ])));

        [$user, $created] = $this->accounts->resolve(
            'apple',
            $claims['sub'],
            $email,
            $name !== '' ? $name : null,
        );

        // Revoking the grant when the account is deleted takes a refresh token,
        // and this one-time code is the only way to get one. Best effort: a
        // failed exchange does not stop the sign-in.
        if (! empty($validated['authorization_code'])) {
            $refreshToken = $this->appleTokens->refreshToken($validated['authorization_code']);

            if ($refreshToken !== null) {
                $this->accounts->rememberRefreshToken('apple', $claims['sub'], $refreshToken);
            }
        }

        return $this->accounts->loginResponse($user, $created, 'apple-token');
    }
}
