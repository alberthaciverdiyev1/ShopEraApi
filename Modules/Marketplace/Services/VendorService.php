<?php

namespace Modules\Marketplace\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Modules\Marketplace\Entities\Vendor;
use Modules\User\Entities\User;

class VendorService
{
    private const TRANSLIT = [
        'ə' => 'e', 'ğ' => 'g', 'ş' => 's', 'ç' => 'c', 'ö' => 'o', 'ü' => 'u', 'ı' => 'i',
        'Ə' => 'e', 'Ğ' => 'g', 'Ş' => 's', 'Ç' => 'c', 'Ö' => 'o', 'Ü' => 'u', 'İ' => 'i',
    ];

    public function store(User $user): ?Vendor
    {
        return Vendor::query()->where('user_id', $user->id)->first();
    }

    public function create(User $user, array $data, Request $request): Vendor
    {
        if ($this->store($user)) {
            throw ValidationException::withMessages(['name' => 'Sizin artıq mağazanız var.']);
        }

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'name' => $this->translations($data['name']),
            'slug' => $this->uniqueSlug($data['name']),
            'description' => isset($data['description']) ? $this->translations($data['description']) : null,
            'phone' => $data['phone'] ?? $user->phone,
            'email' => $data['email'] ?? $user->email,
            'address' => $data['address'] ?? null,
            'status' => Vendor::STATUS_ACTIVE,
        ]);

        if ($request->hasFile('logo')) {
            $vendor->forceFill(['logo' => $request->file('logo')->store('vendors', 'public')])->save();
        }

        // Give the account the vendor role so it can manage its store.
        if (! $user->hasRole('vendor')) {
            $user->assignRole('vendor');
        }

        return $vendor;
    }

    public function update(Vendor $vendor, array $data, Request $request): Vendor
    {
        if (isset($data['name'])) {
            $vendor->name = $this->translations($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $vendor->description = $data['description'] ? $this->translations($data['description']) : null;
        }
        foreach (['phone', 'email', 'address'] as $field) {
            if (array_key_exists($field, $data)) {
                $vendor->{$field} = $data[$field];
            }
        }
        $vendor->save();

        if ($request->hasFile('logo')) {
            $vendor->forceFill(['logo' => $request->file('logo')->store('vendors', 'public')])->save();
        }

        return $vendor->refresh();
    }

    public function findBySlug(string $slug): ?Vendor
    {
        return Vendor::query()->where('slug', $slug)->first();
    }

    public function payload(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'slug' => $vendor->slug,
            'logo' => $vendor->logo,
            'logo_url' => $vendor->logo ? Storage::disk('public')->url($vendor->logo) : null,
            'description' => $vendor->description,
            'phone' => $vendor->phone,
            'email' => $vendor->email,
            'address' => $vendor->address,
            'status' => $vendor->status,
            'listings_count' => $vendor->products()->count(),
        ];
    }

    private function translations(string $value): array
    {
        return array_fill_keys(['az', 'en', 'ru', 'tr'], $value);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug(strtr($name, self::TRANSLIT)) ?: 'magaza';
        $slug = $base;
        $i = 2;

        while (Vendor::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
