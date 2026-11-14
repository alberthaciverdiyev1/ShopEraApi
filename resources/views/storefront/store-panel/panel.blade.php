@php
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };

    $storeName = $localized($payload['name'] ?? '');
    $storeDescription = $localized($payload['description'] ?? '');
    $initial = mb_strtoupper(mb_substr($storeName ?: 'M', 0, 1));
    $isOwner = ! empty($payload['is_owner']);
    $permissions = $payload['permissions'] ?? [];
    $can = fn (string $key): bool => in_array($key, $permissions, true);
    $canSettings = $can('settings');
    $canListings = $can('listings');

    $stats = [
        'total' => count($listings),
        'promoted' => collect($listings)->where('is_promoted', true)->count(),
        'premium' => collect($listings)->where('is_premium', true)->count(),
        'views' => collect($listings)->sum('views'),
        'contacts' => collect($listings)->sum('contact_reveals'),
    ];

    // Map the listing payload onto the shared product card shape.
    $toCard = function (array $listing) use ($localized): array {
        $images = $listing['images'] ?? [];
        $images = $images instanceof \Illuminate\Support\Collection ? $images->all() : (array) $images;

        return [
            'title' => $localized($listing['title'] ?? ''),
            'slug' => $listing['slug'] ?? null,
            'price' => $listing['price'] ?? 0,
            'city' => $localized($listing['city'] ?? '') ?: null,
            'created_at' => $listing['created_at'] ?? null,
            'is_premium' => $listing['is_premium'] ?? false,
            'is_promoted' => $listing['is_promoted'] ?? false,
            'images' => array_map(fn ($path) => ['image_path' => $path], $images),
        ];
    };
@endphp

<x-storefront.layouts.app :title="$storeName.' — Mağaza paneli'">
    <div class="section-padding fix elan-page">
        <div class="container sf-ig">
            <header class="sf-ig__header">
                <div class="sf-ig__avatar">
                    @if (! empty($payload['logo_url']))
                        <img src="{{ $payload['logo_url'] }}" alt="{{ $storeName }}">
                    @else
                        <span>{{ $initial }}</span>
                    @endif
                </div>

                <div class="sf-ig__info">
                    <div class="sf-ig__title-row">
                        <h1>{{ $storeName !== '' ? $storeName : 'Mağazam' }}</h1>
                        <span class="sf-badge">{{ $isOwner ? 'Sahib' : 'Komanda üzvü' }}</span>
                    </div>

                    <div class="sf-ig__stats">
                        <span><strong>{{ number_format($stats['total']) }}</strong> elan</span>
                        <span><strong>{{ number_format($stats['views']) }}</strong> baxış</span>
                        <span><strong>{{ number_format($stats['contacts']) }}</strong> əlaqə</span>
                        <span><strong>{{ number_format($stats['promoted']) }}</strong> irəli çəkilmiş</span>
                    </div>

                    @if ($storeDescription !== '')
                        <p class="sf-ig__bio">{{ $storeDescription }}</p>
                    @endif

                    <div class="sf-ig__actions">
                        @if ($canListings)
                            <a class="theme-btn" href="/elan/ver">
                                <i class="fa-solid fa-plus"></i> Yeni elan
                            </a>
                        @endif
                        <a class="sf-btn sf-btn--ghost sf-btn--sm"
                           href="/magaza/{{ $payload['slug'] ?? '' }}" target="_blank" rel="noopener">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Mağaza səhifəsi
                        </a>
                        <a class="sf-btn sf-btn--ghost sf-btn--sm" href="/magaza/komanda">
                            <i class="fa-solid fa-users"></i> {{ $isOwner ? 'Komanda' : 'İcazələrim' }}
                        </a>
                    </div>
                </div>
            </header>

            <nav class="sf-ig__tabs" aria-label="Mağaza idarəetməsi">
                @if ($canListings)
                    <a href="{{ route('storefront.store.panel', ['tab' => 'listings']) }}"
                       class="{{ $tab === 'listings' ? 'is-active' : '' }}">Elanlar</a>
                @endif
                @if ($canSettings)
                    <a href="{{ route('storefront.store.panel', ['tab' => 'settings']) }}"
                       class="{{ $tab === 'settings' ? 'is-active' : '' }}">Mağaza məlumatları</a>
                @endif
            </nav>

            @if (session('status'))
                <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
            @endif

            @if (! $canListings && ! $canSettings)
                <div class="sf-panel sf-ig__notice">
                    <h6 class="fw-bold mb-2">İcazə tələb olunur</h6>
                    <p class="text-muted small mb-0">
                        Bu mağazada idarəetmə icazəniz yoxdur. Elanları görmək və ya mağaza
                        məlumatlarını dəyişmək üçün mağaza sahibindən icazə istəyin.
                    </p>
                </div>
            @elseif ($tab === 'settings' && $canSettings)
                <section class="sf-ig__panel">
                    <form action="{{ route('storefront.store.panel.update') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <h2 class="sf-ig__panel-title">Mağaza məlumatları</h2>

                        @if ($errors->any())
                            <div class="sf-alert sf-alert--error">{{ $errors->first() }}</div>
                        @endif

                        <div class="sf-account__form">
                            <div class="sf-form-row">
                                <label>
                                    <span>Ad</span>
                                    <input type="text" name="name" value="{{ old('name', $storeName) }}" required maxlength="120">
                                </label>
                                <label>
                                    <span>Telefon</span>
                                    <input type="text" name="phone" value="{{ old('phone', $payload['phone'] ?? '') }}">
                                </label>
                            </div>
                            <label>
                                <span>Təsvir</span>
                                <textarea name="description" rows="3">{{ old('description', $storeDescription) }}</textarea>
                            </label>
                            <div class="sf-form-row">
                                <label>
                                    <span>Ünvan</span>
                                    <input type="text" name="address" value="{{ old('address', $payload['address'] ?? '') }}">
                                </label>
                                <label>
                                    <span>Loqo</span>
                                    <input type="file" name="logo" accept="image/*">
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="theme-btn mt-3">Yadda saxla</button>
                    </form>
                </section>
            @elseif ($tab === 'listings' && $canListings)
                @if (count($listings) === 0)
                    <div class="sf-panel sf-ig__notice">
                        <h6 class="fw-bold mb-2">Hələ elan yoxdur</h6>
                        <p class="text-muted small mb-3">İlk elanınızı yerləşdirin və mağazanızı böyüdün.</p>
                        <a href="/elan/ver" class="theme-btn">Yeni elan</a>
                    </div>
                @else
                    <div class="sf-ig__grid">
                        @foreach ($listings as $listing)
                            <div class="sf-my-listing">
                                <x-storefront.product-card :product="$toCard($listing)" />

                                <div class="sf-my-listing__bar">
                                    <span class="sf-my-listing__stats">
                                        <i class="fa-regular fa-eye"></i> {{ number_format((int) ($listing['views'] ?? 0)) }}
                                        <i class="fa-solid fa-phone"></i> {{ number_format((int) ($listing['contact_reveals'] ?? 0)) }}
                                    </span>
                                </div>

                                <div class="sf-my-listing__actions">
                                    <form action="{{ route('storefront.listings.destroy', $listing['slug'] ?? $listing['id']) }}"
                                          method="POST" onsubmit="return confirm('Elan silinsin?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm sf-btn--danger w-100">Sil</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-storefront.layouts.app>
