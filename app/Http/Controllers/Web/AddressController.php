<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreAddressRequest;
use App\Http\Requests\Storefront\UpdateAddressRequest;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Http\RedirectResponse;
use Modules\User\Http\Controllers\AddressController as ModuleAddressController;
use Modules\User\Http\Requests\Address\AddressAddRequest;
use Modules\User\Http\Requests\Address\AddressUpdateRequest;

class AddressController extends Controller
{
    use InteractsWithModuleControllers;

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $form = $this->moduleRequest(AddressAddRequest::class, $request->validated(), 'POST');

        return $this->backToAddresses(
            app(ModuleAddressController::class)->add($form)->getData(true)
        );
    }

    public function update(UpdateAddressRequest $request, int $id): RedirectResponse
    {
        $form = $this->moduleRequest(AddressUpdateRequest::class, $request->validated(), 'PUT');

        return $this->backToAddresses(
            app(ModuleAddressController::class)->update($id, $form)->getData(true)
        );
    }

    public function destroy(int $id): RedirectResponse
    {
        return $this->backToAddresses(
            app(ModuleAddressController::class)->delete($id)->getData(true)
        );
    }

    private function backToAddresses(array $payload): RedirectResponse
    {
        $success = $payload['success'] ?? false;

        return redirect()
            ->route('storefront.dashboard', ['tab' => 'addresses'])
            ->with($success ? 'status' : 'error', $payload['message'] ?? null);
    }
}
