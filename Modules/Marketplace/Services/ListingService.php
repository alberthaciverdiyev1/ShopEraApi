<?php

namespace Modules\Marketplace\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $listing = Product::create([
            'title' => $this->translations($data['title']),
            'description' => $this->translations($data['description']),
            'category_id' => $data['category_id'],
            'city_id' => $data['city_id'],
            'condition' => $data['condition'],
            'price' => $data['price'],
            'stock_count' => (int) ($data['stock_count'] ?? 1),
            'user_id' => $user?->id,
            'vendor_id' => $vendor?->id,
            'seller_type' => $sellerType,
            'contact_name' => $data['contact_name'] ?? ($user ? trim($user->name.' '.$user->surname) : null),
            'contact_phone' => $data['contact_phone'] ?? $user?->phone,
            'contact_email' => $data['contact_email'] ?? $user?->email,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $manageUrl = null;
        $plainToken = null;

        if ($sellerType === 'guest') {
            // Guests manage their listing through a secret link (no account, no OTP).
            $plainToken = Str::random(48);
            $listing->forceFill(['manage_token' => hash('sha256', $plainToken)])->save();
            $manageUrl = url('/ilan/idaresi/'.$plainToken);
        }

        $this->storeImages($listing, $request);

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

        foreach (['category_id', 'city_id', 'condition', 'price', 'stock_count', 'contact_name', 'contact_phone', 'contact_email'] as $field) {
            if (array_key_exists($field, $data)) {
                $listing->{$field} = $data[$field];
            }
        }

        $listing->save();
        $this->storeImages($listing, $request);

        return $listing;
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
}
