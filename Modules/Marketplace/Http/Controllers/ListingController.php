<?php

namespace Modules\Marketplace\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Http\Requests\StoreListingRequest;
use Modules\Marketplace\Http\Requests\UpdateListingRequest;
use Modules\Marketplace\Http\Resources\ListingResource;
use Modules\Marketplace\Services\ListingService;
use Modules\Product\Entities\Product;

class ListingController extends Controller
{
    public function __construct(private readonly ListingService $service) {}

    /** Public listing feed. Promoted listings surface first. */
    public function index(Request $request)
    {
        $listings = Product::query()
            ->with(['images', 'city', 'vendor'])
            ->where('is_active', true)
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('city_id'), fn ($q) => $q->where('city_id', $request->integer('city_id')))
            ->when($request->filled('condition'), fn ($q) => $q->where('condition', $request->string('condition')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', $request->input('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', $request->input('max_price')))
            ->when($request->filled('q'), fn ($q) => $q->whereRaw("title->>'az' ILIKE ?", ['%'.$request->string('q').'%']))
            ->orderByDesc('is_promoted')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return ListingResource::collection($listings)->additional([
            'success' => true,
            'status_code' => 200,
            'message' => 'OK',
        ]);
    }

    public function show(int $id)
    {
        $listing = Product::query()->with(['images', 'city', 'vendor'])->findOrFail($id);

        return responseHelper('OK', 200, new ListingResource($listing));
    }

    /** Guest listing — no account; returns the secret manage URL. */
    public function storeGuest(StoreListingRequest $request)
    {
        $result = $this->service->create($request->validated(), null, $request);

        return responseHelper('Elan yerləşdirildi.', 201, [
            'listing' => new ListingResource($result['listing']->load('images', 'city')),
            'manage_url' => $result['manage_url'],
        ]);
    }

    /** Signed-in seller (user or vendor). */
    public function store(StoreListingRequest $request)
    {
        $result = $this->service->create($request->validated(), $request->user('sanctum'), $request);

        return responseHelper('Elan yerləşdirildi.', 201, [
            'listing' => new ListingResource($result['listing']->load('images', 'city')),
        ]);
    }

    /** The signed-in seller's own listings. */
    public function myListings(Request $request)
    {
        $listings = Product::query()
            ->with(['images', 'city', 'vendor'])
            ->where('user_id', $request->user('sanctum')->id)
            ->orderByDesc('id')
            ->paginate(20);

        return ListingResource::collection($listings)->additional([
            'success' => true,
            'status_code' => 200,
            'message' => 'OK',
        ]);
    }

    public function update(UpdateListingRequest $request, int $id)
    {
        $listing = Product::query()->findOrFail($id);
        $this->authorizeOwner($listing, $request->user('sanctum')->id);

        $this->service->update($listing, $request->validated(), $request);

        return responseHelper('Elan yeniləndi.', 200, new ListingResource($listing->load('images', 'city')));
    }

    public function destroy(Request $request, int $id)
    {
        $listing = Product::query()->findOrFail($id);
        $this->authorizeOwner($listing, $request->user('sanctum')->id);

        $listing->delete();

        return responseHelper('Elan silindi.', 200);
    }

    public function manageShow(string $token)
    {
        return responseHelper('OK', 200, new ListingResource($this->manageListing($token)->load('images', 'city')));
    }

    public function manageUpdate(UpdateListingRequest $request, string $token)
    {
        $listing = $this->manageListing($token);
        $this->service->update($listing, $request->validated(), $request);

        return responseHelper('Elan yeniləndi.', 200, new ListingResource($listing->load('images', 'city')));
    }

    public function manageDestroy(string $token)
    {
        $this->manageListing($token)->delete();

        return responseHelper('Elan silindi.', 200);
    }

    private function manageListing(string $token): Product
    {
        return $this->service->findByManageToken($token)
            ?? abort(404, __('İdarə linki etibarsızdır.'));
    }

    private function authorizeOwner(Product $listing, int $userId): void
    {
        abort_unless((int) $listing->user_id === $userId, 403, __('Bu elan sizə aid deyil.'));
    }
}
