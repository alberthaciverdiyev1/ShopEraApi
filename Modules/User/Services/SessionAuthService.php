<?php

namespace Modules\User\Services;

use App\Helpers\PhoneHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\User\Entities\User;

/**
 * Session-based counterpart of the API auth flow so the Blade storefront can
 * authenticate the same module users without issuing Sanctum tokens.
 */
class SessionAuthService
{
    public function __construct(private readonly ReferralService $referrals) {}

    public function attempt(string $identifier, string $password, bool $remember = false): User
    {
        $identifier = trim($identifier);

        $user = str_contains($identifier, '@')
            ? User::query()->whereRaw('LOWER(email) = ?', [Str::lower($identifier)])->first()
            : PhoneHelper::wherePhone(User::query(), $identifier)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => 'Telefon/e-poçt və ya şifrə yanlışdır.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'identifier' => 'İstifadəçi bloklanıb.',
            ]);
        }

        Auth::login($user, $remember);
        request()->session()->regenerate();

        return $user;
    }

    public function register(array $data): User
    {
        $phone = PhoneHelper::normalize($data['phone']);

        if ($phone === '') {
            throw ValidationException::withMessages([
                'phone' => 'Düzgün telefon nömrəsi daxil edin.',
            ]);
        }

        if (PhoneHelper::wherePhone(User::query(), $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'Bu telefon nömrəsi artıq istifadə olunur.',
            ]);
        }

        $user = User::query()->create([
            'name' => Str::lower($data['name']),
            'surname' => ! empty($data['surname']) ? Str::lower($data['surname']) : null,
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('user');
        $this->referrals->add($user->id);

        Auth::login($user);
        request()->session()->regenerate();

        return $user;
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
