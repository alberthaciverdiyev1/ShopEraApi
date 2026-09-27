<?php

namespace Modules\User\Services;

use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use Modules\User\Http\Entities\SocialAccount;
use Modules\User\Http\Entities\User;
use Symfony\Component\HttpFoundation\Response as StatusCode;

/**
 * Finds or creates the account behind a Google or Apple sign-in and issues the
 * app's own session for it.
 */
class SocialAccountService
{
    public function __construct(
        private readonly ReferralService $referralService,
        private readonly AppleTokenClient $appleTokens,
    ) {
    }

    /**
     * An identity linked before wins, so the same account is found even when
     * Apple hides the address or Google's changes. Otherwise a verified address
     * finds an existing account. Anything else becomes a new account.
     *
     * A deleted account is never revived: signing in again starts a new one,
     * as registering again with the same phone number does.
     *
     * @param  string  $subject  the provider's stable user id (the token's `sub`)
     * @param  string|null  $email  only an address the provider has verified
     * @return array{0: User, 1: bool} the account, and whether it was just created
     */
    public function resolve(string $provider, string $subject, ?string $email, ?string $name): array
    {
        $email = Str::lower(trim((string) $email));
        $email = $email === '' ? null : $email;

        $linked = $this->linkedUser($provider, $subject);
        $byEmail = $email !== null ? $this->findByEmail($email) : null;

        $user = $linked && ! $linked->trashed() ? $linked : null;
        $user ??= $byEmail && ! $byEmail->trashed() ? $byEmail : null;
        $created = false;

        if (! $user) {
            // A deleted account still holds its address (users.email is
            // unique), and then the new account goes without it.
            [$user, $created] = $this->create($byEmail ? null : $email, $name);
        }

        // Same rule as the phone/password login.
        if (! $created && ! $user->is_active) {
            $this->refuse('User is blocked');
        }

        $this->link($user, $provider, $subject);

        return [$user, $created];
    }

    /**
     * The app's session for the account, in the phone login's response shape.
     * Like that login it keeps one session per account.
     */
    public function loginResponse(User $user, bool $created, string $tokenName)
    {
        $user->tokens()->delete();

        return responseHelper('Login successful.', StatusCode::HTTP_OK, [
            'token' => $user->createToken($tokenName)->plainTextToken,
            'user' => $user->only(['id', 'name', 'email', 'phone']),
            'is_new_user' => $created,
        ]);
    }

    /**
     * Keeps a provider's refresh token for an identity, to revoke it later.
     */
    public function rememberRefreshToken(string $provider, string $subject, string $refreshToken): void
    {
        SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $subject)
            ->first()
            ?->forceFill(['refresh_token' => $refreshToken])
            ->save();
    }

    /**
     * Unlinks an account that is being deleted from its Google and Apple
     * identities. Apple grants are revoked first, as Apple requires; a link
     * whose grant could not be revoked stays, with its token, for a retry.
     * Never throws, so it cannot stop the deletion.
     */
    public function forget(User $user): void
    {
        foreach (SocialAccount::query()->where('user_id', $user->id)->get() as $link) {
            try {
                $token = $link->provider === 'apple' ? $link->refresh_token : null;

                if ($token !== null && ! $this->appleTokens->revoke($token)) {
                    continue;
                }

                $link->delete();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Ends the request with a 403. responseHelper() translates the message.
     */
    public function refuse(string $message): never
    {
        throw new HttpResponseException(responseHelper($message, StatusCode::HTTP_FORBIDDEN));
    }

    private function linkedUser(string $provider, string $subject): ?User
    {
        $userId = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $subject)
            ->value('user_id');

        return $userId ? User::withTrashed()->find($userId) : null;
    }

    private function findByEmail(string $email): ?User
    {
        // Older accounts may hold the address with capitals.
        return User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();
    }

    /**
     * @return array{0: User, 1: bool}
     */
    private function create(?string $email, ?string $name): array
    {
        try {
            $user = User::create([
                // Lower-cased, as the phone registration stores names.
                'name' => Str::lower(trim((string) $name)) ?: $this->fallbackName($email),
                'email' => $email,
                'email_verified_at' => now(),
                // users.phone is NOT NULL and neither provider shares the
                // number. The duplicate-phone trigger lets any number of
                // accounts share an empty phone, but not '0000000000', which
                // older accounts already hold. The user can add a real number
                // in the profile.
                'phone' => '',
                // Nobody knows it: the password form signs in by phone. The
                // model hashes it.
                'password' => Str::random(40),
            ]);
        } catch (QueryException $exception) {
            // Two sign-ins for the same new address at once: the other request
            // created the account first.
            $existing = $email !== null && (string) $exception->getCode() === '23505'
                ? $this->findByEmail($email)
                : null;

            if (! $existing) {
                throw $exception;
            }

            return [$existing, false];
        }

        $user->assignRole('user');
        $this->referralService->add($user->id);

        return [$user, true];
    }

    private function link(User $user, string $provider, string $subject): void
    {
        try {
            // Moves the identity over when it belonged to an account since
            // deleted.
            SocialAccount::updateOrCreate(
                ['provider' => $provider, 'provider_user_id' => $subject],
                ['user_id' => $user->id],
            );
        } catch (QueryException $exception) {
            // The same identity signing in twice at once: the other request
            // linked it.
            if ((string) $exception->getCode() !== '23505') {
                throw $exception;
            }
        }
    }

    private function fallbackName(?string $email): string
    {
        // Apple's relay addresses are random strings, useless as a name.
        if ($email === null || str_ends_with($email, '@privaterelay.appleid.com')) {
            return 'user';
        }

        return Str::before($email, '@');
    }
}
