@php
    $localized = function ($value): string {
        if (is_array($value)) {
            return $value['az'] ?? $value['en'] ?? (string) reset($value);
        }

        return (string) ($value ?? '');
    };
    $total = (int) ($meta['total'] ?? count($vendors));
@endphp

<x-storefront.layouts.app
    :title="'Mağazalar — ShopEra'"
    description="ShopEra-dakı rəsmi mağazalar"
    :categories="$categories">

    <x-storefront.breadcrumb title="Mağazalar" parent="Ana səhifə" />

    <div class="container sf-store">
        <header class="sf-store__head sf-store__head--list">
            <div class="sf-store__info">
                <h1>Mağazalar</h1>
                <p class="sf-store__description">
                    ShopEra-da rəsmi mağazalar. Mağazaya keçid edərək bütün elanlarına baxa bilərsiniz.
                </p>
                <p class="sf-store__meta">{{ number_format($total) }} mağaza</p>
            </div>
        </header>

        @if (count($vendors) > 0)
            <div class="sf-store-grid">
                @foreach ($vendors as $vendor)
                    @php
                        $name = $localized($vendor['name'] ?? '');
                        $description = $localized($vendor['description'] ?? '');
                        $initial = mb_strtoupper(mb_substr($name ?: 'M', 0, 1));
                        $href = '/magaza/'.($vendor['slug'] ?? '');
                    @endphp
                    <a class="sf-store-card" href="{{ $href }}">
                        <span class="sf-store-card__cover">
                            <span class="sf-store-card__logo">
                                @if (! empty($vendor['logo_url']))
                                    <img src="{{ $vendor['logo_url'] }}" alt="{{ $name }}" loading="lazy">
                                @else
                                    {{ $initial }}
                                @endif
                            </span>
                            @if (! empty($vendor['rating_count']))
                                <span class="sf-store-card__rating">
                                    <i class="fa-solid fa-star"></i>
                                    {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }}
                                </span>
                            @endif
                        </span>

                        <span class="sf-store-card__body">
                            <strong>{{ $name }}</strong>
                            @if ($description)
                                <small>{{ \Illuminate\Support\Str::limit($description, 90) }}</small>
                            @endif

                            <span class="sf-store-card__meta">
                                <span><i class="fa-regular fa-rectangle-list"></i> {{ number_format((int) ($vendor['listings_count'] ?? 0)) }} elan</span>
                                @if (! empty($vendor['address']))
                                    <span><i class="fa-regular fa-location-dot"></i> {{ \Illuminate\Support\Str::limit($vendor['address'], 22) }}</span>
                                @endif
                            </span>

                            <span class="sf-store-card__cta">
                                Mağazaya bax <i class="fa-solid fa-arrow-right"></i>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>

            <x-storefront.pagination :meta="$meta" />
        @else
            <div class="sf-empty">
                <i class="fa-regular fa-store"></i>
                <h2>Hələ mağaza yoxdur</h2>
                <p>İlk mağazanı siz açın.</p>
                <a href="/magaza/aciq" class="sf-btn sf-btn--primary">Mağaza aç</a>
            </div>
        @endif
    </div>
</x-storefront.layouts.app>
