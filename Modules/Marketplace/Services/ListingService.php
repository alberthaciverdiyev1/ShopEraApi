<?php

namespace Modules\Marketplace\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Category\Entities\Category;
use Modules\Delivery\Entities\City;
use Modules\Filter\Entities\Filter;
use Modules\Filter\Entities\FilterValue;
use Modules\Filter\Entities\ProductFilterValue;
use Modules\Marketplace\Entities\ListingReport;
use Modules\Marketplace\Entities\Vendor;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;

class ListingService
{
    /**
     * Create a marketplace listing. The seller is a vendor when the account
     * owns a store, otherwise a plain user; a null account means a guest.
     *
     * @return array{listing: Product, manage_url: ?string, manage_token: ?string}
     */
    public function create(array $data, ?User $user, Request $request): array
    {
        $vendor = $user ? Vendor::query()->where('user_id', $user->id)->first() : null;
        $sellerType = ! $user ? 'guest' : ($vendor ? 'vendor' : 'user');

        // Hybrid taxonomy: some leaf categories require a brand.
        $category = Category::query()->find($data['category_id']);
        if ($category?->needs_brand && empty($data['brand_id'])) {
            throw ValidationException::withMessages([
                'brand_id' => 'Bu kateqoriya üçün marka seçmək lazımdır.',
            ]);
        }

        $listing = Product::create([
            'title' => isset($data['title']) ? $this->translations($data['title']) : null,
            'description' => isset($data['description']) ? $this->translations($data['description']) : null,
            'category_id' => $data['category_id'],
            'brand_id' => $data['brand_id'] ?? null,
            'model' => $data['model'] ?? null,
            'city_id' => $this->resolveCityId($data['city_key'] ?? null),
            'condition' => $data['condition'] ?? null,
            'has_delivery' => filter_var($data['has_delivery'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'price' => $data['price'] ?? 0,
            'stock_count' => (int) ($data['stock_count'] ?? 1),
            'user_id' => $user?->id,
            'vendor_id' => $vendor?->id,
            'seller_type' => $sellerType,
            'contact_name' => $data['contact_name'] ?? ($user ? trim($user->name.' '.$user->surname) : null),
            'contact_phone' => $data['contact_phone'] ?? $user?->phone,
            'contact_email' => $data['contact_email'] ?? $user?->email,
            'is_active' => true,
            'expires_at' => now()->addDays($this->listingDays()),
            'approval_status' => 'approved',
        ]);

        $manageUrl = null;
        $plainToken = null;

        if ($sellerType === 'guest') {
            // Guests manage their listing through a secret link (no account, no OTP).
            $plainToken = Str::random(48);
            $listing->forceFill(['manage_token' => hash('sha256', $plainToken)])->save();
            $manageUrl = url('/elan/idaresi/'.$plainToken);
        }

        $this->storeImages($listing, $request);
        $this->syncFilterValues($listing, $data['filter_values'] ?? []);

        return ['listing' => $listing, 'manage_url' => $manageUrl, 'manage_token' => $plainToken];
    }

    public function update(Product $listing, array $data, Request $request): Product
    {
        if (array_key_exists('title', $data)) {
            $listing->title = $this->translations($data['title']);
        }
        if (array_key_exists('description', $data)) {
            $listing->description = $this->translations($data['description']);
        }

        foreach (['category_id', 'brand_id', 'model', 'condition', 'price', 'stock_count', 'contact_name', 'contact_phone', 'contact_email'] as $field) {
            if (array_key_exists($field, $data)) {
                $listing->{$field} = $data[$field];
            }
        }

        if (array_key_exists('has_delivery', $data)) {
            $listing->has_delivery = filter_var($data['has_delivery'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('city_key', $data)) {
            $listing->city_id = $this->resolveCityId($data['city_key']);
        }

        $listing->save();
        $this->storeImages($listing, $request);

        if (array_key_exists('filter_values', $data)) {
            $this->syncFilterValues($listing, $data['filter_values'] ?? []);
        }

        return $listing;
    }

    /**
     * Reveal the seller's contact details for a listing and count the reveal.
     * Falls back to the seller account and, for a vendor, the store.
     */
    public function contact(Product $listing): array
    {
        $listing->increment('contact_reveals');

        $user = $listing->user;
        $vendor = $listing->vendor;

        return [
            'name' => $listing->contact_name
                ?: ($vendor?->name ?: ($user ? trim($user->name.' '.$user->surname) : null)),
            'phone' => $listing->contact_phone ?: $user?->phone ?: $vendor?->phone,
            'email' => $listing->contact_email ?: $user?->email ?: $vendor?->email,
        ];
    }

    /** Extend a listing's lifetime and re-activate it. */
    public function renew(Product $listing): void
    {
        $listing->forceFill([
            'expires_at' => now()->addDays($this->listingDays()),
            'is_active' => true,
        ])->save();
    }

    /** Default listing lifetime in days (admin setting, 30 by default). */
    private function listingDays(): int
    {
        try {
            $days = (int) app(\Modules\Setting\Services\SettingService::class)->current()?->listing_active_days;
        } catch (\Throwable) {
            $days = 0;
        }

        return $days > 0 ? $days : 30;
    }

    /** Record a report against a listing (guest or signed-in). */
    public function report(Product $listing, ?User $user, array $data): ListingReport
    {
        return ListingReport::query()->create([
            'product_id' => $listing->id,
            'user_id' => $user?->id,
            'reason' => $data['reason'],
            'comment' => $data['comment'] ?? null,
        ]);
    }

    public function findByManageToken(string $token): ?Product
    {
        return Product::query()
            ->where('seller_type', 'guest')
            ->where('manage_token', hash('sha256', $token))
            ->first();
    }

    private function storeImages(Product $listing, Request $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        foreach ((array) $request->file('images') as $file) {
            $listing->images()->create([
                'image_path' => $file->store('listings', 'public'),
            ]);
        }
    }

    /** Every listing is stored under each locale; translation comes later. */
    private function translations(string $value): array
    {
        return ['az' => $value, 'en' => $value, 'ru' => $value, 'tr' => $value];
    }

    /** Stores the chosen filter values, enforcing the dependency chain. */
    private function syncFilterValues(Product $listing, array $valueIds): void
    {
        ProductFilterValue::query()->where('product_id', $listing->id)->delete();

        $valueIds = array_values(array_filter(array_map('intval', $valueIds)));

        if ($valueIds === []) {
            return;
        }

        $values = FilterValue::query()->with('filter')->whereIn('id', $valueIds)->get();
        $byFilter = $values->keyBy('filter_id');

        foreach ($values as $value) {
            $filter = $value->filter;

            // A dependent value must hang off the value selected in its parent filter.
            if ($filter && $filter->depends_on_filter_id && $value->parent_value_id) {
                $parent = $byFilter->get($filter->depends_on_filter_id);

                if (! $parent || (int) $parent->id !== (int) $value->parent_value_id) {
                    throw ValidationException::withMessages([
                        'filter_values' => 'Filtrlər arasındakı asılılıq düzgün deyil.',
                    ]);
                }
            }
        }

        $required = Filter::query()
            ->where('category_id', $listing->category_id)
            ->where('required', true)
            ->pluck('id');

        foreach ($required as $filterId) {
            if (! $byFilter->has($filterId)) {
                throw ValidationException::withMessages([
                    'filter_values' => 'Bütün məcburi filtrləri seçin.',
                ]);
            }
        }

        foreach ($values as $value) {
            ProductFilterValue::query()->create([
                'product_id' => $listing->id,
                'filter_id' => $value->filter_id,
                'filter_value_id' => $value->id,
            ]);
        }
    }

    /** The storefront sends a city key (the /city endpoint has no ids). */
    private function resolveCityId(?string $key): ?int
    {
        if (! $key) {
            return null;
        }

        return City::query()->where('key', $key)->value('id');
    }
}
