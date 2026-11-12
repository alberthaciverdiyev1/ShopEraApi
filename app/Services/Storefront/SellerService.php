<?php

namespace App\Services\Storefront;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Brand\Services\BrandService;
use Modules\Delivery\Services\DeliveryService;
use Modules\Filter\Services\FilterService;
use Modules\Marketplace\Entities\PromotionPackage;
use Modules\Marketplace\Http\Resources\ListingResource;
use Modules\Marketplace\Services\ListingService;
use Modules\Marketplace\Services\PromotionService;
use Modules\Marketplace\Support\ListingFields;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

/**
 * Seller-side listing reads and writes for the storefront pages. Creation and
 * promotion reuse the marketplace services the API already exposes.
 */
class SellerService
{
    public function __construct(
        private readonly ListingService $listings,
        private readonly PromotionService $promotions,
        private readonly FilterService $filters,
        private readonly CatalogService $catalog,
        private readonly DeliveryService $delivery,
        private readonly BrandService $brands,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mine(User $user): array
    {
        $listings = Product::query()
            ->with(['images', 'city', 'vendor'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return ListingResource::collection($listings)->resolve(request());
    }

    public function findOwned(int $id, User $user): Product
    {
        $listing = Product::query()->findOrFail($id);

        if ((int) $listing->user_id !== (int) $user->id) {
            abort(403);
        }

        return $listing;
    }

    public function delete(int $id, User $user): void
    {
        $this->findOwned($id, $user)->delete();
    }

    public function renew(int $id, User $user): void
    {
        $this->listings->renew($this->findOwned($id, $user));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function packages(): array
    {
        return $this->promotions->packages()->map(fn (PromotionPackage $package) => [
            'id' => $package->id,
            'name' => $package->name,
            'description' => $package->description,
            'type' => $package->type,
            'days' => (int) $package->days,
            'price' => (float) $package->price,
        ])->all();
    }

    public function promote(int $id, User $user, int $packageId): void
    {
        $listing = $this->findOwned($id, $user);
        $package = PromotionPackage::query()->findOrFail($packageId);

        $this->promotions->createOrder($listing, $user, $package);
    }

    /**
     * @return array{listing: Product, manage_url: ?string, manage_token: ?string}
     */
    public function create(array $data, ?User $user, Request $request): array
    {
        return $this->listings->create($data, $user, $request);
    }

    /**
     * Guest listing addressed by its secret management token.
     */
    public function findManaged(string $token): Product
    {
        return $this->listings->findByManageToken($token)
            ?? abort(404, 'İdarə linki etibarsızdır və ya elan silinib.');
    }

    public function updateManaged(Product $listing, array $data, Request $request): Product
    {
        return $this->listings->update($listing, $data, $request);
    }

    public function deleteManaged(Product $listing): void
    {
        $listing->delete();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function categoryTree(): array
    {
        return $this->catalog->categories();
    }

    /**
     * Walk the category tree and return the ancestor chain down to $id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function categoryPath(int $id): array
    {
        $walk = function (array $nodes, array $path) use (&$walk, $id): ?array {
            foreach ($nodes as $node) {
                $children = $node['children'] ?? [];

                if ($children instanceof ResourceCollection) {
                    $children = $children->resolve();
                }

                $children = (array) $children;
                $next = array_merge($path, [$node]);

                if ((int) $node['id'] === $id) {
                    return $next;
                }

                if ($children && ($found = $walk($children, $next))) {
                    return $found;
                }
            }

            return null;
        };

        return $walk($this->categoryTree(), []) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(?int $categoryId): array
    {
        return ListingFields::forCategory($categoryId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function filterTree(?int $categoryId): array
    {
        if (! $categoryId) {
            return [];
        }

        return $this->filters->tree($categoryId)->getData(true)['data'] ?? [];
    }

    /**
     * @return array<int, array{key: string, name: string}>
     */
    public function cities(): array
    {
        return $this->delivery->cities()->getData(true)['data'] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function brands(): array
    {
        return $this->brands->list(request())->getData(true)['data'] ?? [];
    }
}
