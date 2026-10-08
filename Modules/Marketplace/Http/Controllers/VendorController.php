<?php

namespace Modules\Marketplace\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\VendorService;

class VendorController extends Controller
{
    public function __construct(private readonly VendorService $service) {}

    /** The signed-in user's store, or null. */
    public function mine(Request $request)
    {
        $vendor = $this->service->store($request->user('sanctum'));

        return responseHelper('OK', 200, $vendor ? $this->service->payload($vendor) : null);
    }

    /** Open a store for the signed-in user. */
    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $vendor = $this->service->create($request->user('sanctum'), $data, $request);

        return responseHelper('Mağaza yaradıldı.', 201, $this->service->payload($vendor));
    }

    public function update(Request $request)
    {
        $vendor = $this->service->store($request->user('sanctum'));
        abort_unless($vendor, 404, 'Mağazanız yoxdur.');

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $vendor = $this->service->update($vendor, $data, $request);

        return responseHelper('Mağaza yeniləndi.', 200, $this->service->payload($vendor));
    }

    /** Public store page data. */
    public function show(string $slug)
    {
        $vendor = $this->service->findBySlug($slug);
        abort_unless($vendor, 404);

        return responseHelper('OK', 200, $this->service->payload($vendor));
    }
}
