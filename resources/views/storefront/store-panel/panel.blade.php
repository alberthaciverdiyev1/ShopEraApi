@php
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };

    $fallback = '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
    $stats = [
        'total' => count($listings),
        'promoted' => collect($listings)->where('is_promoted', true)->count(),
        'vip' => collect($listings)->where('is_vip', true)->count(),
        'premium' => collect($listings)->where('is_premium', true)->count(),
        'views' => collect($listings)->sum('views'),
        'contacts' => collect($listings)->sum('contact_reveals'),
    ];
@endphp

<x-storefront.layouts.app :title="$localized($payload['name'] ?? '').' — Mağaza paneli'">
    <div class="section-padding fix elan-page">
        <div class="container elan-page__narrow">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <h2 class="mb-0">
                    {{ $localized($payload['name'] ?? '') }}
                    <span class="text-muted fs-6">— Mağaza paneli</span>
                </h2>
                <div class="d-flex gap-2">
                    <a class="sf-btn sf-btn--ghost" href="/magaza/{{ $payload['slug'] ?? '' }}" target="_blank">Mağaza səhifəsi</a>
                    <a class="theme-btn" href="/elan/ver">Yeni elan</a>
                </div>
            </div>

            @if (session('status'))
                <div class="sf-alert sf-alert--success">{{ session('status') }}</div>
            @endif

            <div class="sf-store-stats">
                @foreach ([
                    'Elanlar' => $stats['total'],
                    'İrəli çəkilmiş' => $stats['promoted'],
                    'VIP' => $stats['vip'],
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

            <div class="row g-4">
                <div class="col-lg-5">
                    <form action="{{ route('storefront.store.panel.update') }}" method="POST" enctype="multipart/form-data" class="sf-panel">
                        @csrf
                        @method('PUT')
                        <h6 class="fw-bold mb-3">Mağaza məlumatları</h6>

                        @if ($errors->any())
                            <div class="sf-alert sf-alert--error">{{ $errors->first() }}</div>
                        @endif

                        <div class="sf-account__form">
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
                            <button type="submit" class="theme-btn">Yadda saxla</button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-7">
                    <div class="sf-panel">
                        <h6 class="fw-bold mb-3">Elanlarım</h6>
                        @if (count($listings) === 0)
                            <p class="text-muted mb-0">Hələ elanınız yoxdur.</p>
                        @else
                            <div class="d-flex flex-column gap-2">
                                @foreach ($listings as $listing)
                                    <div class="d-flex align-items-center gap-3 border-bottom pb-2">
                                        <img src="{{ $listing['images'][0] ?? $fallback }}" alt=""
                                             style="width:52px;height:52px;object-fit:cover;border-radius:8px;">
                                        <div class="flex-grow-1 min-w-0">
                                            <p class="mb-0 fw-semibold text-truncate">{{ $localized($listing['title'] ?? '') }}</p>
                                            <p class="mb-0 small text-primary">{{ number_format((float) ($listing['price'] ?? 0), 2) }} ₼</p>
                                            <p class="mb-0 text-muted" style="font-size:12px;">
                                                <i class="fa-regular fa-eye"></i> {{ $listing['views'] ?? 0 }} · <i class="fa-solid fa-phone"></i> {{ $listing['contact_reveals'] ?? 0 }}
                                            </p>
                                        </div>
                                        <form action="{{ route('storefront.listings.destroy', $listing['id']) }}" method="POST"
                                              onsubmit="return confirm('Elan silinsin?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm sf-btn--danger">Sil</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-storefront.layouts.app>
