<?php

namespace Modules\Marketplace\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Marketplace\Entities\PromotionPackage;
use Modules\Marketplace\Entities\Vendor;
use Modules\Marketplace\Http\Requests\StoreListingRequest;
use Modules\Marketplace\Http\Requests\UpdateListingRequest;
use Modules\Marketplace\Http\Resources\ListingResource;
use Modules\Marketplace\Services\ListingService;
use Modules\Marketplace\Services\PromotionService;
use Modules\Marketplace\Services\VendorTeamService;
use Modules\Marketplace\Support\ListingFields;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

class ListingController extends Controller
{
    public function __construct(private readonly ListingService $service, private readonly PromotionService $promotions) {}

    /** Re-publish the owner's expired/archived listing. */
    public function renew(Request $request, int $id)
    {
        $listing = Product::query()->findOrFail($id);
        $this->authorizeManage($listing, $request->user('sanctum'));
        $this->service->renew($listing);

        return responseHelper('Elan yenidən dərc edildi.', 200, new ListingResource($listing->fresh()));
    }

    /** Active promotion packages (public). */
    public function packages()
    {
        return responseHelper('OK', 200, $this->promotions->packages());
    }

    /** Create a promotion order for the signed-in owner's listing. */
    public function promote(Request $request, int $id)
    {
        $data = $request->validate(['package_id' => ['required', 'exists:promotion_packages,id']]);
        $listing = Product::query()->findOrFail($id);
        $this->authorizeManage($listing, $request->user('sanctum'), 'promotions');
        $package = PromotionPackage::query()->findOrFail($data['package_id']);

        $order = $this->promotions->createOrder($listing, $request->user('sanctum'), $package);

        return responseHelper('Promosiya sifarişi yaradıldı.', 201, [
            'id' => $order->id,
            'status' => $order->status,
            'price' => (float) $order->price,
        ]);
    }

    /** Report a listing (public, signed-in users are recorded). */
    public function report(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(['spam', 'fraud', 'wrong_category', 'offensive', 'duplicate', 'other'])],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $listing = Product::query()->findOrFail($id);
        $this->service->report($listing, $request->user('sanctum'), $data);

        return responseHelper('Şikayətiniz qeydə alındı.', 201);
    }

    /** Reveal the seller's contact details for a listing (public). */
    public function contact(int $id)
    {
        $listing = Product::query()->with(['user', 'vendor'])->findOrFail($id);

        return responseHelper('OK', 200, $this->service->contact($listing));
    }

    /** The listing form schema (visible/required fields) for a category. */
    public function fields(Request $request)
    {
        $categoryId = (int) $request->query('category_id', 0);

        return responseHelper('OK', 200, ListingFields::forCategory($categoryId ?: null));
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

    /** The signed-in seller's own listings, plus the store's when allowed. */
    public function myListings(Request $request)
    {
        $user = $request->user('sanctum');
        $team = app(VendorTeamService::class);
        $vendor = $team->vendorFor($user);
        $vendorId = ($vendor && $team->can($user, $vendor, 'listings')) ? $vendor->id : null;

        $listings = Product::query()
            ->with(['images', 'city', 'vendor'])
            ->where(function ($query) use ($user, $vendorId) {
                $query->where('user_id', $user->id);

                if ($vendorId) {
                    $query->orWhere('vendor_id', $vendorId);
                }
            })
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
        $this->authorizeManage($listing, $request->user('sanctum'));

        $this->service->update($listing, $request->validated(), $request);

        return responseHelper('Elan yeniləndi.', 200, new ListingResource($listing->load('images', 'city')));
    }

    public function destroy(Request $request, int $id)
    {
        $listing = Product::query()->findOrFail($id);
        $this->authorizeManage($listing, $request->user('sanctum'));

        $listing->delete();

        return responseHelper('Elan silindi.', 200);
    }

    public function manageShow(string $token)
    {
        return responseHelper('OK', 200, new ListingResource($this->manageListing($token)->load('images', 'city', 'colors', 'sizes')));
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

    /** The listing's own creator or a team member allowed to manage it. */
    private function authorizeManage(Product $listing, User $user, string $permission = 'listings'): void
    {
        if ((int) $listing->user_id === (int) $user->id) {
            return;
        }

        $vendor = $listing->vendor_id ? Vendor::query()->find($listing->vendor_id) : null;

        abort_unless(
            $vendor && app(VendorTeamService::class)->can($user, $vendor, $permission),
            403,
            __('Bu elan sizə aid deyil.')
        );
    }
}
