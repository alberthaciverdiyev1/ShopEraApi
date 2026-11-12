@props(['product'])

@php
    $images = collect($product['images'] ?? []);
    $image = $images->firstWhere('image_path')['image_path']
        ?? '/assets/images/top-deals-item/topDealsItemThumb1_1.png';

    $price = (float) ($product['price'] ?? 0);
    $discountPrice = (float) ($product['discount'] ?? 0);
    $hasDiscount = $discountPrice > 0 && $discountPrice < $price;
    $displayPrice = $hasDiscount ? $discountPrice : $price;
    $discountPercent = $hasDiscount ? (int) round((($price - $displayPrice) / max($price, 0.01)) * 100) : 0;

    $url = ! empty($product['slug'])
        ? '/elan/'.$product['slug']
        : '/shop/details?id='.($product['id'] ?? '');

    $placement = ! empty($product['is_premium'])
        ? ['label' => 'Premium', 'class' => 'is-premium']
        : (! empty($product['is_vip'])
            ? ['label' => 'VIP', 'class' => 'is-vip']
            : (! empty($product['is_promoted'])
                ? ['label' => 'İrəli çəkildi', 'class' => 'is-promoted']
                : null));

    $listedAt = ! empty($product['created_at'])
        ? \Illuminate\Support\Carbon::parse($product['created_at'])
        : null;

    $listingMeta = collect([
        $product['city'] ?? 'Bakı',
        $listedAt?->isToday()
            ? 'Bu gün, '.$listedAt->format('H:i')
            : ($listedAt?->isYesterday()
                ? 'Dünən, '.$listedAt->format('H:i')
                : $listedAt?->format('d.m.Y, H:i')),
    ])->filter()->implode(', ');
@endphp

<article class="sf-product-card best-seller-product-items-two item-border">
    <a class="card-link" href="{{ $url }}" aria-label="{{ $product['title'] ?? '' }}"></a>

    <div class="icon-box2">
        <a href="/login" class="fav-btn" aria-label="Sevimlilərə əlavə et">
            <i class="fa-regular fa-heart"></i>
        </a>
    </div>

    @if ($discountPercent > 0)
        <span class="sf-product-card__badge">-{{ $discountPercent }}%</span>
    @endif

    <div class="best-seller-product-items-two__thumb">
        <img src="{{ $image }}" alt="{{ $product['title'] ?? '' }}" loading="lazy">

        @if (! empty($product['rate']))
            <div class="card-rating">
                <i class="fa-solid fa-star"></i>
                <b>{{ number_format((float) $product['rate'], 1) }}</b>
                <span>({{ (int) ($product['rate_count'] ?? 0) }})</span>
            </div>
        @endif

        @if ($placement)
            <span class="card-placement {{ $placement['class'] }}">{{ $placement['label'] }}</span>
        @endif
    </div>

    <div class="best-seller-product-items-two__content">
        <div class="best-seller-product-items-two__details">
            <div class="best-seller-product-items-two__details--price">
                <span class="offer-price">{{ number_format($displayPrice, 2) }} ₼</span>
                @if ($hasDiscount)
                    <span class="original-price">{{ number_format($price, 2) }} ₼</span>
                @endif
            </div>

            <h6 class="best-seller-product-items-two__details--title">
                <a href="{{ $url }}">{{ $product['title'] ?? '' }}</a>
            </h6>

            @if ($listingMeta)
                <a href="{{ $url }}" class="listing-card-meta">{{ $listingMeta }}</a>
            @endif
        </div>
    </div>
</article>
