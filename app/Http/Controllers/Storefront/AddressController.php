<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreAddressRequest;
use App\Http\Requests\Storefront\UpdateAddressRequest;
use Illuminate\Http\RedirectResponse;
use Modules\User\Services\AddressService;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses) {}

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $response = $this->addresses->add($request);

        return $this->backToAddresses($response->getData(true));
    }

    public function update(UpdateAddressRequest $request, int $id): RedirectResponse
    {
        $response = $this->addresses->update($id, $request);

        return $this->backToAddresses($response->getData(true));
    }

    public function destroy(int $id): RedirectResponse
    {
        $response = $this->addresses->delete($id);

        return $this->backToAddresses($response->getData(true));
    }

    private function backToAddresses(array $payload): RedirectResponse
    {
        $success = $payload['success'] ?? false;

        return redirect()
            ->route('storefront.dashboard', ['tab' => 'addresses'])
            ->with($success ? 'status' : 'error', $payload['message'] ?? null);
    }
}
