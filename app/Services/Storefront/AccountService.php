<?php

namespace App\Services\Storefront;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Delivery\Services\DeliveryService;
use Modules\User\Services\AddressService;
use Modules\User\Services\UserService;

/**
 * Storefront account reads and writes.
 *
 * The mobile API keeps its Sanctum + per-field endpoints; the Blade pages run
 * on the session guard and reuse the same module services behind them.
 */
class AccountService
{
    public function __construct(
        private readonly UserService $users,
        private readonly AddressService $addresses,
        private readonly DeliveryService $delivery,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function addresses(): array
    {
        return $this->addresses->getAll(request())->getData(true)['data'] ?? [];
    }

    public function defaultAddress(): ?array
    {
        $addresses = $this->addresses();

        foreach ($addresses as $address) {
            if (! empty($address['is_default'])) {
                return $address;
            }
        }

        return $addresses[0] ?? null;
    }

    /**
     * @return array<int, array{key: string, name: string}>
     */
    public function cities(): array
    {
        return $this->delivery->cities()->getData(true)['data'] ?? [];
    }

    public function updateProfile(array $data): void
    {
        $user = Auth::user();

        $fields = [
            'name' => 'changeName',
            'surname' => 'changeSurname',
            'email' => 'changeEmail',
        ];

        foreach ($fields as $field => $method) {
            $value = isset($data[$field]) ? trim((string) $data[$field]) : '';

            if ($value === '' || $value === (string) $user->{$field}) {
                continue;
            }

            $request = Request::create('/api/user/'.$field, 'PUT', [$field => $value]);
            $request->setUserResolver(fn () => $user);

            $this->users->{$method}($request);
        }

        Auth::setUser($user->fresh());
    }

    public function uploadAvatar(Request $request): void
    {
        $this->users->changeAvatar($request);
        Auth::setUser(Auth::user()->fresh());
    }

    public function removeAvatar(): void
    {
        $request = Request::create('/api/user/remove-avatar', 'DELETE');
        $request->setUserResolver(fn () => Auth::user());

        $this->users->removeAvatar($request);
        Auth::setUser(Auth::user()->fresh());
    }
}
