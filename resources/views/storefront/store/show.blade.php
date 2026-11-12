@php
    $name = is_array($vendor['name'] ?? null)
        ? ($vendor['name']['az'] ?? reset($vendor['name']))
        : ($vendor['name'] ?? 'Mağaza');
    $description = is_array($vendor['description'] ?? null)
        ? ($vendor['description']['az'] ?? reset($vendor['description']))
        : ($vendor['description'] ?? null);
    $initial = mb_strtoupper(mb_substr($name ?: 'M', 0, 1));
@endphp

<x-storefront.layouts.app
    :title="$name.' — ShopEra'"
    :description="$description ?: ($name.' mağazasının elanları')"
    :categories="$categories">

    <x-storefront.breadcrumb :title="$name" parent="Mağazalar" />

    <div class="container sf-store">
        <header class="sf-store__head">
            <div class="sf-store__logo">
                @if (! empty($vendor['logo_url']))
                    <img src="{{ $vendor['logo_url'] }}" alt="{{ $name }}">
                @else
                    <span>{{ $initial }}</span>
                @endif
            </div>

            <div class="sf-store__info">
                <h1>{{ $name }}</h1>

                @if (! empty($vendor['rating_count']))
                    <div class="sf-store__rating">
                        <i class="fa-solid fa-star"></i>
                        {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }}
                        <small>({{ (int) $vendor['rating_count'] }} rəy)</small>
                    </div>
                @endif

                @if ($description)
                    <p class="sf-store__description">{{ $description }}</p>
                @endif

                <p class="sf-store__meta">
                    {{ number_format((int) ($vendor['listings_count'] ?? 0)) }} elan
                    @if (! empty($vendor['address'])) · {{ $vendor['address'] }} @endif
                    @if (! empty($vendor['phone'])) · <a href="tel:{{ $vendor['phone'] }}">{{ $vendor['phone'] }}</a> @endif
                </p>
            </div>
        </header>

        @if (count($items) > 0)
            <div class="sf-product-grid">
                @foreach ($items as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>

            <x-storefront.pagination :meta="$meta" />
        @else
            <div class="sf-empty">
                <i class="fa-regular fa-store"></i>
                <h2>Bu mağazada hələ elan yoxdur</h2>
                <a href="/shop" class="sf-btn sf-btn--primary">Bütün elanlar</a>
            </div>
        @endif
    </div>
</x-storefront.layouts.app>
