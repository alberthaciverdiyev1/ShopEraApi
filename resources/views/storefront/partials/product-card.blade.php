@php
    $title = is_array($product->title)
        ? ($product->title[app()->getLocale()] ?? $product->title['az'] ?? $product->title['en'] ?? reset($product->title))
        : $product->title;
    $image = $product->images->first()?->image_path;
    $price = $product->price;
    if ($price === null && $product->relationLoaded('sizes') && $product->sizes->isNotEmpty()) {
        $price = $product->sizes->min(fn ($size) => $size->pivot?->price);
    }
    $discount = $product->discount;
    if ($discount === null && $product->relationLoaded('sizes') && $product->sizes->isNotEmpty()) {
        $discount = $product->sizes->min(fn ($size) => $size->pivot?->discount);
    }
    $price = (float) ($price ?? 0);
    $discount = (float) ($discount ?? 0);
    $hasDiscount = $discount > 0 && $price > $discount;
    $discountPercent = $hasDiscount ? max(1, round((($price - $discount) / $price) * 100)) : 0;
    $displayPrice = $hasDiscount ? $discount : $price;
    $title = \Illuminate\Support\Str::ucfirst(trim((string) $title));
@endphp
<article class="product-card">
    <a class="product-media" href="{{ route('storefront.product', $product) }}" aria-label="{{ $title }}">
        @if($image)
            <img src="{{ $image }}" alt="{{ $title }}" loading="lazy">
        @endif
        @if($hasDiscount)
            <span class="badge">-{{ $discountPercent }}%</span>
            <span class="ribbon">Fleş Endirim</span>
        @endif
    </a>
    <div class="product-body">
        <div class="rating">
            <span class="star">★</span>
            <strong>{{ number_format((float) ($product->reviews_avg_rate ?? 0), 1) }}</strong>
            ({{ (int) ($product->reviews_count ?? 0) }}) · {{ (int) ($product->views ?? 0) }} baxış
        </div>
        <h3 class="product-title">{{ \Illuminate\Support\Str::limit($title, 42) }}</h3>
        <div class="price-row">
            <div>
                @if($hasDiscount)
                    <span class="old-price">{{ number_format($price, 2) }}₼</span>
                @endif
                <span class="price">{{ number_format($displayPrice, 2) }}₼</span>
            </div>
            <a class="details-link" href="{{ route('storefront.product', $product) }}">Detallar</a>
        </div>
    </div>
</article>
