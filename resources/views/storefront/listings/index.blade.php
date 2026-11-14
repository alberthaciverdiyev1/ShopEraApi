@php
    $fallback = '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };
    $localeTitle = fn (array $listing) => $localized($listing['title'] ?? '');

    // The storefront card expects product-shaped payloads: images as rows with
    // an image_path, so the listing payload is reshaped here instead of
    // copying the card markup.
    $toCard = function (array $listing) use ($localized, $localeTitle): array {
        return [
            'title' => $localeTitle($listing),
            'slug' => $listing['slug'] ?? '',
            'price' => $listing['price'] ?? 0,
            'discount' => $listing['discount'] ?? null,
            'city' => $localized($listing['city'] ?? ''),
            'created_at' => $listing['created_at'] ?? null,
            'is_promoted' => ! empty($listing['is_promoted']),
            'is_premium' => ! empty($listing['is_premium']),
            'images' => collect($listing['images'] ?? [])
                ->map(fn ($image) => is_array($image) ? $image : ['image_path' => $image])
                ->values()
                ->all(),
        ];
    };
@endphp

<x-storefront.layouts.app :title="'Elanlarım — ShopEra'">
    <x-storefront.breadcrumb title="Elanlarım" parent="Hesabım" />

    <div class="section-padding fix elan-page">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <h2 class="mb-0">Elanlarım</h2>
                <a href="/elan/ver" class="theme-btn">Yeni elan</a>
            </div>

            @if (session('status'))
                <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
            @endif

            @if (count($listings) === 0)
                <p class="text-muted">Hələ elanınız yoxdur.</p>
            @else
                <div class="sf-product-grid">
                    @foreach ($listings as $listing)
                        @php
                            $expired = ($listing['is_active'] ?? true) === false
                                || (! empty($listing['expires_at']) && \Illuminate\Support\Carbon::parse($listing['expires_at'])->isPast());
                            $listingSlug = $listing['slug'] ?? $listing['id'];
                        @endphp
                        <div class="sf-my-listing">
                            <x-storefront.product-card :product="$toCard($listing)" />

                            <div class="sf-my-listing__bar">
                                <span class="sf-my-listing__stats">
                                    <i class="fa-regular fa-eye"></i> {{ $listing['views'] ?? 0 }}
                                    <i class="fa-solid fa-phone"></i> {{ $listing['contact_reveals'] ?? 0 }}
                                </span>

                                @if ($expired)
                                    <span class="sf-my-listing__state is-expired">Müddəti bitib</span>
                                @elseif (! empty($listing['expires_at']))
                                    <span class="sf-my-listing__state">
                                        Bitir: {{ \Illuminate\Support\Carbon::parse($listing['expires_at'])->format('d.m.Y') }}
                                    </span>
                                @endif
                            </div>

                            <div class="sf-my-listing__actions">
                                @if ($expired)
                                    <form action="{{ route('storefront.listings.renew', $listingSlug) }}" method="POST" class="sf-my-listing__action">
                                        @csrf
                                        <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm w-100">Yenidən dərc</button>
                                    </form>
                                @else
                                    <button type="button" class="sf-btn sf-btn--ghost sf-btn--sm sf-my-listing__action"
                                            data-sf-promote-open="{{ $listingSlug }}"
                                            data-sf-promote-title="{{ $localeTitle($listing) }}">İrəli çək</button>
                                @endif
                                <form action="{{ route('storefront.listings.destroy', $listingSlug) }}" method="POST"
                                      onsubmit="return confirm('Elan silinsin?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm sf-btn--danger">Sil</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="sf-promote-overlay" data-sf-promote-modal hidden>
        <div class="sf-promote-modal" role="dialog" aria-modal="true" aria-label="İrəli çək">
            <div class="sf-promote-modal__head">
                <h5 data-sf-promote-heading>İrəli çək</h5>
                <button type="button" class="sf-promote-modal__close" data-sf-promote-close aria-label="Bağla">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            @if (count($packages) === 0)
                <p class="text-muted mb-0">Hazırda paket yoxdur.</p>
            @else
                <form method="POST" data-sf-promote-form>
                    @csrf
                    <div class="sf-promote-options">
                        @foreach ($packages as $package)
                            @php
                                $packageName = is_array($package['name'])
                                    ? ($package['name']['az'] ?? reset($package['name']))
                                    : $package['name'];
                            @endphp
                            <label class="sf-promote-option">
                                <input type="radio" name="package_id" value="{{ $package['id'] }}" required>
                                <span>{{ $packageName }}</span>
                                <span class="ms-auto fw-bold">{{ number_format($package['price'], 2) }} ₼</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="theme-btn">Sifariş et</button>
                </form>
            @endif
        </div>
    </div>
</x-storefront.layouts.app>
