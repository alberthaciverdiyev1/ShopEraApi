@props(['product'])

@php
    $sellerType = $product['seller_type'] ?? null;
    $sellerName = $product['seller']['name']
        ?? $product['contact_name']
        ?? $product['user']['name']
        ?? 'ShopEra istifadəçisi';
    $sellerLabel = $sellerType === 'store'
        ? 'Mağaza'
        : ($sellerType === 'company' ? 'Şirkət' : 'Satıcı');

    $listedAt = ! empty($product['created_at'])
        ? \Illuminate\Support\Carbon::parse($product['created_at'])
        : null;

    $condition = $product['condition'] ?? null;
@endphp

<div class="sf-product-summary">
    <h1 class="sf-product-summary__title">{{ $product['title'] ?? '' }}</h1>

    <div class="sf-product-summary__meta">
        @if (! empty($product['rate']))
            <span class="sf-product-summary__rating">
                <i class="fa-solid fa-star"></i>
                {{ number_format((float) $product['rate'], 1) }}
                <small>({{ (int) ($product['rate_count'] ?? 0) }} rəy)</small>
            </span>
        @endif
        @if (! empty($product['city']))
            <span><i class="fa-regular fa-location-dot"></i> {{ $product['city'] }}</span>
        @endif
        @if (! empty($product['views']))
            <span><i class="fa-regular fa-eye"></i> {{ number_format((int) $product['views']) }} baxış</span>
        @endif
        @if ($listedAt)
            <span><i class="fa-regular fa-clock"></i> {{ $listedAt->format('d.m.Y, H:i') }}</span>
        @endif
    </div>

    <div class="sf-product-summary__price">
        <span class="sf-product-summary__price-current">{{ number_format((float) $product['current_price'], 2) }} ₼</span>
        @if (! empty($product['has_discount']))
            <span class="sf-product-summary__price-old">{{ number_format((float) $product['original_price'], 2) }} ₼</span>
            <span class="sf-product-summary__price-badge">-{{ (int) $product['discount_percent'] }}%</span>
        @endif
    </div>

    <div class="sf-product-summary__facts">
        <div class="sf-product-summary__fact">
            <span>Elan kodu</span>
            <b>{{ $product['product_code'] }}</b>
        </div>
        @if ($condition)
            <div class="sf-product-summary__fact">
                <span>Vəziyyəti</span>
                <b>{{ $condition }}</b>
            </div>
        @endif
        @if (isset($product['stock_count']))
            <div class="sf-product-summary__fact">
                <span>Stok</span>
                <b>{{ (int) $product['stock_count'] }} ədəd</b>
            </div>
        @endif
        <div class="sf-product-summary__fact">
            <span>Yerləşdirilib</span>
            <b>{{ $listedAt?->format('d.m.Y') ?? '—' }}</b>
        </div>
    </div>

    <div class="sf-product-seller">
        <div class="sf-product-seller__avatar">
            <i class="fa-regular fa-store"></i>
        </div>
        <div class="sf-product-seller__body">
            <span class="sf-product-seller__label">{{ $sellerLabel }}</span>
            <strong>{{ $sellerName }}</strong>
            @if (! empty($product['seller']['rating_count']))
                <span class="sf-product-seller__rating">
                    <i class="fa-solid fa-star"></i>
                    {{ number_format((float) ($product['seller']['rating_avg'] ?? 0), 1) }}
                    ({{ (int) $product['seller']['rating_count'] }})
                </span>
            @endif
        </div>
    </div>

    <div class="sf-product-contact" data-sf-contact data-listing="{{ $product['slug'] }}">
        <button type="button" class="sf-btn sf-btn--primary sf-product-contact__reveal" data-sf-contact-reveal>
            Nömrəni göstər
        </button>

        <div class="sf-product-contact__details" data-sf-contact-details hidden></div>

        <p class="sf-product-contact__error" data-sf-contact-error hidden>Əlaqə məlumatını yükləmək mümkün olmadı.</p>

        @auth
            <button type="button" class="sf-btn sf-btn--ghost sf-product-contact__chat" data-sf-lchat-open>
                <i class="fa-solid fa-comment-dots"></i> Satıcıya yaz
            </button>
        @else
            <a href="/login" class="sf-btn sf-btn--ghost sf-product-contact__chat">
                <i class="fa-solid fa-comment-dots"></i> Satıcıya yaz
            </a>
        @endauth
    </div>

    <div class="sf-product-summary__actions">
        <a href="/login" class="sf-btn sf-btn--ghost"><i class="fa-regular fa-heart"></i> Sevimlilərə əlavə et</a>
    </div>
</div>
