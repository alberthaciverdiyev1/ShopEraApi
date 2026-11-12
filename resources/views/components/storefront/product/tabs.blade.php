@props([
    'product' => [],
])

@php
    $description = trim(strip_tags((string) ($product['description'] ?? '')));
    $specs = $product['specs'] ?? [];
    $characteristics = $product['characteristics'] ?? [];
    $reviews = $product['reviews'] ?? [];
    $reviewCount = (int) ($product['review_count'] ?? count($reviews));
@endphp

<section class="sf-product-tabs" data-sf-tabs>
    <div class="sf-product-tabs__nav" role="tablist">
        <button type="button" class="sf-product-tabs__tab is-active" data-sf-tab="description" role="tab">
            Təsvir
        </button>
        <button type="button" class="sf-product-tabs__tab" data-sf-tab="specs" role="tab">
            Xüsusiyyətlər
        </button>
        <button type="button" class="sf-product-tabs__tab" data-sf-tab="reviews" role="tab">
            Rəylər @if ($reviewCount) <span>({{ $reviewCount }})</span> @endif
        </button>
    </div>

    <div class="sf-product-tabs__panel is-active" data-sf-panel="description" role="tabpanel">
        @if ($description !== '')
            <div class="sf-product-tabs__text">
                {!! nl2br(e($description)) !!}
            </div>
        @else
            <p class="sf-product-tabs__empty">Təsvir əlavə edilməyib.</p>
        @endif
    </div>

    <div class="sf-product-tabs__panel" data-sf-panel="specs" role="tabpanel" hidden>
        @if ($specs !== [] || $characteristics !== [])
            <div class="sf-spec-grid">
                @foreach (array_merge($specs, $characteristics) as $spec)
                    <div class="sf-spec-grid__row">
                        <span class="sf-spec-grid__label">{{ $spec['label'] }}</span>
                        <span class="sf-spec-grid__value">
                            @if (! empty($spec['link']))
                                <a href="{{ $spec['link'] }}">{{ $spec['value'] }}</a>
                            @else
                                {{ $spec['value'] }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="sf-product-tabs__empty">Xüsusiyyət əlavə edilməyib.</p>
        @endif
    </div>

    <div class="sf-product-tabs__panel" data-sf-panel="reviews" role="tabpanel" hidden>
        @if (count($reviews) > 0)
            <ul class="sf-reviews">
                @foreach ($reviews as $review)
                    <li class="sf-review">
                        <div class="sf-review__head">
                            <strong>{{ $review['user']['name'] ?? 'İstifadəçi' }}</strong>
                            <span class="sf-review__stars" aria-label="{{ (int) ($review['rate'] ?? 0) }} ulduz">
                                @for ($star = 1; $star <= 5; $star++)
                                    <i class="fa-{{ $star <= (int) ($review['rate'] ?? 0) ? 'solid' : 'regular' }} fa-star"></i>
                                @endfor
                            </span>
                            <time>{{ $review['created_at'] ?? '' }}</time>
                        </div>
                        @if (! empty($review['comment']))
                            <p>{{ $review['comment'] }}</p>
                        @endif
                        @if (! empty($review['image']))
                            <img class="sf-review__image" src="{{ $review['image'] }}" alt="" loading="lazy">
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="sf-product-tabs__empty">Hələ rəy yoxdur.</p>
        @endif

        <div class="sf-product-tabs__review-cta">
            <p>Bu elanla bağlı rəyinizi paylaşın.</p>
            <a href="/login" class="sf-btn sf-btn--primary">Rəy yazmaq üçün daxil olun</a>
        </div>
    </div>
</section>
