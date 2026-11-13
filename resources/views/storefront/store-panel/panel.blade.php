@php
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };

    $storeName = $localized($payload['name'] ?? '');
    $initial = mb_strtoupper(mb_substr($storeName ?: 'M', 0, 1));
    $canSettings = in_array('settings', $payload['permissions'] ?? ['settings'], true);

    $stats = [
        'total' => count($listings),
        'promoted' => collect($listings)->where('is_promoted', true)->count(),
        'premium' => collect($listings)->where('is_premium', true)->count(),
        'views' => collect($listings)->sum('views'),
        'contacts' => collect($listings)->sum('contact_reveals'),
    ];
@endphp

<x-storefront.layouts.app :title="$storeName.' — Mağaza paneli'">
    <div class="section-padding fix elan-page">
        <div class="container sf-dash">
            <aside class="sf-dash__side">
                <div class="sf-dash__store">
                    <span class="sf-dash__logo">
                        @if (! empty($payload['logo_url']))
                            <img src="{{ $payload['logo_url'] }}" alt="{{ $storeName }}">
                        @else
                            {{ $initial }}
                        @endif
                    </span>
                    <span class="sf-dash__store-info">
                        <strong>{{ $storeName }}</strong>
                        <small>{{ number_format($stats['total']) }} elan</small>
                    </span>
                </div>

                <nav class="sf-dash__nav" aria-label="Mağaza idarəetməsi">
                    <a href="/magaza/panel" class="active">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>İcmal</span>
                    </a>
                    <a href="#listings">
                        <i class="fa-regular fa-rectangle-list"></i>
                        <span>Elanlar</span>
                        <em>{{ number_format($stats['total']) }}</em>
                    </a>
                    @if ($canSettings)
                        <a href="#settings">
                            <i class="fa-solid fa-store"></i>
                            <span>Mağaza məlumatları</span>
                        </a>
                    @endif
                    <a href="/magaza/komanda">
                        <i class="fa-solid fa-users"></i>
                        <span>Komanda</span>
                    </a>
                    <a href="/magaza/{{ $payload['slug'] ?? '' }}" target="_blank" rel="noopener">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span>Mağaza səhifəsi</span>
                    </a>

                    <span class="sf-dash__sep"></span>

                    <a href="/dashboard">
                        <i class="fa-solid fa-user"></i>
                        <span>Hesabım</span>
                    </a>
                    <a href="/elan/ver" class="sf-dash__cta">
                        <i class="fa-solid fa-plus"></i>
                        <span>Yeni elan</span>
                    </a>
                </nav>
            </aside>

            <div class="sf-dash__main">
                <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                    <h2 class="mb-0">
                        Mağaza paneli
                        @if ($storeName !== '')
                            <span class="text-muted fs-6">— {{ $storeName }}</span>
                        @endif
                    </h2>
                    <div class="d-flex gap-2">
                        <a class="sf-btn sf-btn--ghost" href="/magaza/komanda">Komanda</a>
                        <a class="theme-btn" href="/elan/ver">Yeni elan</a>
                    </div>
                </div>

                @if (session('status'))
                    <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
                @endif

                <div class="sf-store-stats" id="overview">
                    @foreach ([
                        'Elanlar' => $stats['total'],
                        'İrəli çəkilmiş' => $stats['promoted'],
                        'Premium' => $stats['premium'],
                        'Baxış' => $stats['views'],
                        'Əlaqə' => $stats['contacts'],
                    ] as $label => $value)
                        <div class="sf-store-stat">
                            <span>{{ $label }}</span>
                            <strong>{{ number_format($value) }}</strong>
                        </div>
                    @endforeach
                </div>

                <section class="sf-panel mt-4" id="listings">
                    <h6 class="fw-bold mb-3">Elanlarım</h6>
                    @if (count($listings) === 0)
                        <p class="text-muted mb-0">Hələ elanınız yoxdur.</p>
                    @else
                        <div class="sf-product-grid sf-dash-listings">
                            @foreach ($listings as $listing)
                                @php
                                    // Map the listing payload onto the shared product
                                    // card shape (translatable title, image objects).
                                    $images = $listing['images'] ?? [];
                                    $images = $images instanceof \Illuminate\Support\Collection ? $images->all() : (array) $images;

                                    $card = [
                                        'title' => $localized($listing['title'] ?? ''),
                                        'slug' => $listing['slug'] ?? null,
                                        'price' => $listing['price'] ?? 0,
                                        'city' => $localized($listing['city'] ?? '') ?: null,
                                        'created_at' => $listing['created_at'] ?? null,
                                        'is_premium' => $listing['is_premium'] ?? false,
                                        'is_promoted' => $listing['is_promoted'] ?? false,
                                        'images' => array_map(
                                            fn ($path) => ['image_path' => $path],
                                            $images
                                        ),
                                    ];
                                @endphp
                                <div class="sf-dash-listing">
                                    <x-storefront.product-card :product="$card" />

                                    <div class="sf-dash-listing__actions">
                                        <span>
                                            <i class="fa-regular fa-eye"></i> {{ number_format((int) ($listing['views'] ?? 0)) }}
                                            · <i class="fa-solid fa-phone"></i> {{ number_format((int) ($listing['contact_reveals'] ?? 0)) }}
                                        </span>
                                        <form action="{{ route('storefront.listings.destroy', $listing['slug'] ?? $listing['id']) }}"
                                              method="POST" onsubmit="return confirm('Elan silinsin?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm sf-btn--danger">Sil</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                @if ($canSettings)
                    <section class="sf-panel mt-4" id="settings">
                        <form action="{{ route('storefront.store.panel.update') }}" method="POST"
                              enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <h6 class="fw-bold mb-3">Mağaza məlumatları</h6>

                            @if ($errors->any())
                                <div class="sf-alert sf-alert--error">{{ $errors->first() }}</div>
                            @endif

                            <div class="sf-account__form sf-account__form--grid">
                                <label>
                                    <span>Ad</span>
                                    <input type="text" name="name" value="{{ old('name', $localized($payload['name'] ?? '')) }}" required maxlength="120">
                                </label>
                                <label>
                                    <span>Təsvir</span>
                                    <textarea name="description" rows="3">{{ old('description', $localized($payload['description'] ?? '')) }}</textarea>
                                </label>
                                <label>
                                    <span>Telefon</span>
                                    <input type="text" name="phone" value="{{ old('phone', $payload['phone'] ?? '') }}">
                                </label>
                                <label>
                                    <span>Ünvan</span>
                                    <input type="text" name="address" value="{{ old('address', $payload['address'] ?? '') }}">
                                </label>
                                <label>
                                    <span>Loqo</span>
                                    <input type="file" name="logo" accept="image/*">
                                </label>
                            </div>
                            <button type="submit" class="theme-btn">Yadda saxla</button>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
