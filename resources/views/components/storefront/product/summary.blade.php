@props(['product'])

@php
    // seller_type comes from the API as guest, user or vendor.
    $sellerType = $product['seller_type'] ?? null;
    $sellerSlug = data_get($product, 'seller.slug');
    $isStore = $sellerType === 'vendor' && ! empty($sellerSlug);
    $sellerName = data_get($product, 'seller.name')
        ?? data_get($product, 'contact_name')
        ?? data_get($product, 'user.name')
        ?? 'ShopEra istifadəçisi';
    $sellerLabel = $sellerType === 'vendor'
        ? 'Mağaza'
        : ($sellerType === 'guest' ? 'Qonaq' : 'İstifadəçi');
    $sellerUrl = $isStore ? '/magaza/'.$sellerSlug : null;

    $listedAt = ! empty($product['created_at'])
        ? \Illuminate\Support\Carbon::parse($product['created_at'])
        : null;

    $condition = $product['condition'] ?? null;
    $brandName = $product['brand']['name'] ?? null;
    $brandSlug = $product['brand']['slug'] ?? null;
    $rate = (float) ($product['rate'] ?? 0);
    $rateCount = (int) ($product['rate_count'] ?? 0);
    $currentPrice = (float) ($product['current_price'] ?? 0);
    $originalPrice = (float) ($product['original_price'] ?? 0);
    $hasDiscount = ! empty($product['has_discount']);
    $views = (int) ($product['views'] ?? 0);

    // Per-size prices drive the price block when a size is picked.
    $sizePrices = [];
    $generalStock = (int) ($product['stock_count'] ?? 0);

    foreach ($product['sizes'] ?? [] as $size) {
        if (! empty($size['id'])) {
            $sizePrices[(string) $size['id']] = [
                'price' => (float) ($size['price'] ?? 0),
                'discount' => (float) ($size['discount'] ?? 0),
                'stock' => isset($size['stock']) ? (int) $size['stock'] : null,
            ];
        }
    }

    $localizedName = function ($name): string {
        if (is_array($name)) {
            return (string) ($name['az'] ?? $name['en'] ?? reset($name));
        }

        return (string) ($name ?? '');
    };

    // Build top 4 highlight chips
    $highlights = [];
    if (! empty($condition)) {
        $highlights[] = ['label' => 'Vəziyyəti', 'value' => $condition];
    }
    if (! empty($product['city'])) {
        $highlights[] = ['label' => 'Şəhər', 'value' => $product['city']];
    }
    if (! empty($product['gender'])) {
        $highlights[] = ['label' => 'Üslub', 'value' => $product['gender']];
    }
    if (! empty($product['product_code'])) {
        $highlights[] = ['label' => 'Elan kodu', 'value' => $product['product_code']];
    }
    if (count($highlights) < 4 && ! empty($product['specs'])) {
        foreach ($product['specs'] as $spec) {
            if (count($highlights) >= 4) break;
            $highlights[] = ['label' => $spec['label'], 'value' => $spec['value']];
        }
    }
@endphp

<div class="sf-product-summary">
    <!-- Brand (Trendyol Style Bold Header) -->
    @if ($brandName)
        <div class="sf-product-brand">
            @if ($brandSlug)
                <a href="/shop?brand={{ $brandSlug }}">{{ $brandName }}</a>
            @else
                <span>{{ $brandName }}</span>
            @endif
        </div>
    @endif

    <!-- Product Title -->
    <h1 class="sf-product-summary__title">{{ $product['title'] ?? '' }}</h1>

    <!-- Rating & Reviews line -->
    <div class="sf-product-rating-bar">
        <div class="sf-product-rating-main" data-sf-goto-reviews>
            @if ($rateCount > 0)
                <span class="sf-product-rating-score">{{ number_format($rate, 1) }}</span>
                <div class="sf-product-rating-stars" aria-label="{{ number_format($rate, 1) }} ulduz">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="fa-solid fa-star @if ($i <= round($rate)) is-filled @endif"></i>
                    @endfor
                </div>
                <span class="sf-product-rating-count">• {{ $rateCount }} rəy</span>
            @else
                <span class="sf-product-rating-count">Hələ rəy yoxdur — ilk rəyi siz yazın</span>
            @endif
        </div>
        @if ($views >= 2)
            <span class="sf-product-rating-views" title="{{ number_format($views) }} baxış">
                <i class="fa-regular fa-eye"></i> {{ number_format($views) }}
            </span>
        @endif
    </div>

    <!-- Prices -->
    <div class="sf-product-summary__price" data-sf-price
         data-price="{{ $currentPrice }}"
         data-original-price="{{ $originalPrice }}"
         data-has-discount="{{ $hasDiscount ? '1' : '0' }}"
         data-discount-percent="{{ (int) ($product['discount_percent'] ?? 0) }}">
        <span class="sf-product-summary__price-current" data-sf-price-current>{{ number_format($currentPrice, 2) }} ₼</span>
        <span class="sf-product-summary__price-old" data-sf-price-old @if (! $hasDiscount) hidden @endif>{{ number_format($originalPrice, 2) }} ₼</span>
        <span class="sf-product-summary__price-badge" data-sf-price-badge @if (! $hasDiscount) hidden @endif>-{{ (int) ($product['discount_percent'] ?? 0) }}%</span>
    </div>

    <div class="sf-product-stock" data-sf-stock-line>
        <span class="sf-product-stock__badge @if ($generalStock <= 0) is-out @endif" data-sf-stock-badge>
            {{ $generalStock > 0 ? 'Stokda: '.$generalStock.' ədəd' : 'Stokda yoxdur' }}
        </span>
    </div>


    <!-- Key Product Highlights (2x2 Grid Chips - Trendyol Screenshot 2) -->
    @if (count($highlights) > 0)
        <div class="sf-product-highlights">
            @foreach ($highlights as $hl)
                <div class="sf-highlight-chip">
                    <span class="sf-highlight-chip__label">{{ $hl['label'] }}:</span>
                    <strong class="sf-highlight-chip__val">{{ $hl['value'] }}</strong>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Color Variant Selector (Trendyol Screenshot 2) -->
    @if (! empty($product['colors']) && count($product['colors']) > 0)
        <div class="sf-product-variant-section sf-color-variants">
            <div class="sf-variant-header">
                <span class="sf-variant-title">Rəng: <strong data-sf-color-label>{{ $localizedName($product['colors'][0]['name'] ?? '') }}</strong></span>
                <span class="sf-variant-info" title="Mövcud rəng variantları"><i class="fa-solid fa-circle-info"></i></span>
            </div>
            <div class="sf-color-swatches" role="radiogroup" aria-label="Rəng seçimi">
                @foreach ($product['colors'] as $idx => $color)
                    <button type="button"
                            class="sf-color-swatch-card @if ($idx === 0) is-active @endif"
                            data-sf-color-option
                            data-color-id="{{ $color['id'] ?? '' }}"
                            data-color-name="{{ $localizedName($color['name'] ?? '') }}"
                            role="radio"
                            aria-checked="{{ $idx === 0 ? 'true' : 'false' }}">
                        <span class="sf-color-circle" style="background-color: {{ $color['hex'] ?? '#94a3b8' }};"></span>
                        <span class="sf-color-name">{{ $localizedName($color['name'] ?? '') }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Size Variant Selector (Trendyol Screenshot 2) -->
    @if (! empty($product['sizes']) && count($product['sizes']) > 0)
        <div class="sf-product-variant-section sf-size-variants" data-sf-size-prices="{{ json_encode($sizePrices) }}"
             data-sf-general-stock="{{ $generalStock }}">
            <div class="sf-variant-header">
                <span class="sf-variant-title">Ölçü: <strong data-sf-size-label>{{ $localizedName($product['sizes'][0]['name'] ?? '') }}</strong></span>
            </div>
            <div class="sf-size-pills" role="radiogroup" aria-label="Ölçü seçimi">
                @foreach ($product['sizes'] as $idx => $size)
                    <button type="button"
                            class="sf-size-pill @if ($idx === 0) is-active @endif"
                            data-sf-size-option
                            data-size-id="{{ $size['id'] ?? '' }}"
                            data-size-name="{{ $localizedName($size['name'] ?? '') }}"
                            role="radio"
                            aria-checked="{{ $idx === 0 ? 'true' : 'false' }}">
                        {{ $localizedName($size['name'] ?? '') }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Seller Box (Trendyol Marketplace Style) -->
    <div class="sf-product-seller-box">
        <div class="sf-seller-box__avatar">
            <i class="fa-solid {{ $isStore ? 'fa-store' : 'fa-user' }}"></i>
        </div>
        <div class="sf-seller-box__info">
            <div class="sf-seller-box__row">
                @if ($sellerUrl)
                    <a href="{{ $sellerUrl }}" class="sf-seller-box__name">{{ $sellerName }}</a>
                @else
                    <span class="sf-seller-box__name">{{ $sellerName }}</span>
                @endif
                <span class="sf-seller-box__type">{{ $sellerLabel }}</span>
            </div>
            @if (! empty($product['seller']['rating_avg']))
                <div class="sf-seller-box__rating">
                    <span class="sf-seller-rating-pill">
                        <i class="fa-solid fa-star"></i> {{ number_format((float) $product['seller']['rating_avg'], 1) }}
                    </span>
                    <span class="sf-seller-rating-count">({{ (int) ($product['seller']['rating_count'] ?? 0) }} rəy)</span>
                </div>
            @endif
        </div>
        @if ($sellerUrl)
            <div class="sf-seller-box__cta">
                <a href="{{ $sellerUrl }}" class="sf-seller-link">Mağazaya keç <i class="fa-solid fa-chevron-right"></i></a>
            </div>
        @endif
    </div>

    <!-- Contact & Action Buttons -->
    <div class="sf-product-contact" data-sf-contact data-listing="{{ $product['slug'] }}">
        <button type="button" class="sf-btn sf-btn--primary sf-product-contact__reveal" data-sf-contact-reveal>
            <i class="fa-solid fa-phone"></i> Nömrəni göstər
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
        <button type="button"
                class="sf-btn sf-btn--ghost sf-product-summary__fav-btn @if (! empty($product['is_favorite'])) is-favorited @endif"
                data-sf-fav-toggle
                data-slug="{{ $product['slug'] ?? '' }}"
                data-auth="{{ auth()->check() ? '1' : '0' }}">
            <i class="@if (! empty($product['is_favorite'])) fa-solid @else fa-regular @endif fa-heart"></i>
            <span>{{ ! empty($product['is_favorite']) ? 'Sevimlilərdən çıxar' : 'Sevimlilərə əlavə et' }}</span>
        </button>
    </div>
</div>

