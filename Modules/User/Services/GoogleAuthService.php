<?php

namespace Modules\User\Services;

use Laravel\Socialite\Facades\Socialite;
use Modules\User\Http\Entities\User;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class GoogleAuthService
{
    public function __construct(
        private readonly GoogleIdTokenVerifier $verifier,
        private readonly SocialAccountService $accounts,
    ) {
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Browser flow. The shape of this response is what the in-app WebView
     * scrapes for a token, so it is left exactly as it was.
     */
    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        [$user] = $this->account(
            (string) $googleUser->getId(),
            (string) $googleUser->getEmail(),
            $googleUser->getName(),
            $googleUser->user['email_verified'] ?? false,
        );

        $token = $user->createToken('google-token')->plainTextToken;

        return [
            'text' => 'Teymur/cd.app',
            'token' => $token,
        ];
    }

    /**
     * Native flow: the app shows the phone's own Google account picker and
     * posts the ID token it gets back.
     */
    public function loginWithToken($request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        // Config, not env(): production runs `config:cache`, where env() reads
        // back null and no audience would be checked at all.
        if ($this->verifier->audiences() === []) {
            return responseHelper('Google login is not configured.', StatusCode::HTTP_SERVICE_UNAVAILABLE);
        }

        $claims = $this->verifier->verify($request->input('token'));

        if (! $claims || empty($claims['email']) || empty($claims['sub'])) {
            return responseHelper('Invalid Google ID Token', StatusCode::HTTP_FORBIDDEN);
        }

        [$user, $created] = $this->account(
            (string) $claims['sub'],
            $claims['email'],
            $claims['name'] ?? null,
            $claims['email_verified'] ?? false,
        );

        return $this->accounts->loginResponse($user, $created, 'google-token');
    }

    /**
     * @return array{0: User, 1: bool}
     */
    private function account(string $subject, string $email, ?string $name, mixed $emailVerified): array
    {
        if ($subject === '' || trim($email) === '') {
            $this->accounts->refuse('Invalid Google ID Token');
        }

        // Accounts are also matched by address, so one Google has not verified
        // says nothing about who is signing in.
        if (! filter_var($emailVerified, FILTER_VALIDATE_BOOLEAN)) {
            $this->accounts->refuse('Google account email is not verified.');
        }

        return $this->accounts->resolve('google', $subject, $email, $name);
    }
}
