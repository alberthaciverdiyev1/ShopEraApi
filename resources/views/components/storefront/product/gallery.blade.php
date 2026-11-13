@props([
    'images' => [],
    'title' => '',
    'product' => [],
])

@php
    // Each photo can carry the colour and the size it shows; the gallery
    // follows the variant the shopper picks below the image.
    $galleryItems = collect($product['images'] ?? [])
        ->map(fn ($item) => [
            'src' => $item['image_path'] ?? null,
            'color_id' => $item['color_id'] ?? null,
            'size_id' => $item['size_id'] ?? null,
        ])
        ->filter(fn ($item) => ! empty($item['src']))
        ->values()
        ->all();

    $images = array_values(array_filter((array) $images));
    $images = $images === [] ? ['/assets/images/top-deals-item/topDealsItemThumb1_1.png'] : $images;

    if ($galleryItems === []) {
        $galleryItems = array_map(
            fn ($src) => ['src' => $src, 'color_id' => null, 'size_id' => null],
            $images
        );
    }

    $hasDelivery = ! empty($product['has_delivery']);
    $hasDiscount = ! empty($product['has_discount']);
    $isPremium = ! empty($product['is_premium']);
    $isFavorite = ! empty($product['is_favorite']);
    $videoUrl = collect($product['videos'] ?? [])
        ->pluck('video_path')
        ->filter()
        ->first();
@endphp

<div class="sf-gallery" data-sf-gallery>
    <div class="sf-gallery__stage" data-sf-gallery-stage>
        <!-- Top-left Badges (Trendyol Style) -->
        <div class="sf-gallery__badges">
            @if ($hasDelivery)
                <span class="sf-gallery__badge sf-gallery__badge--delivery">
                    <i class="fa-solid fa-truck-fast"></i> PULSUZ ÇATDIRILMA
                </span>
            @endif
            @if ($hasDiscount)
                <span class="sf-gallery__badge sf-gallery__badge--discount">
                    <i class="fa-solid fa-tags"></i> ENDİRİMLİ
                </span>
            @endif
            @if ($isPremium)
                <span class="sf-gallery__badge sf-gallery__badge--vip">
                    <i class="fa-solid fa-crown"></i> PREMIUM
                </span>
            @endif
        </div>

        <!-- Top-right Floating Action Buttons (Trendyol Style) -->
        <div class="sf-gallery__floating-actions">
            <button type="button"
                    class="sf-gallery__action-btn sf-gallery__action-btn--fav @if ($isFavorite) is-favorited @endif"
                    data-sf-fav-toggle
                    data-slug="{{ $product['slug'] ?? '' }}"
                    data-auth="{{ auth()->check() ? '1' : '0' }}"
                    title="Sevimlilərə əlavə et"
                    aria-label="Sevimlilərə əlavə et">
                <i class="@if ($isFavorite) fa-solid @else fa-regular @endif fa-heart"></i>
            </button>

            <button type="button"
                    class="sf-gallery__action-btn sf-gallery__action-btn--share"
                    data-sf-share-btn
                    data-title="{{ $title }}"
                    title="Paylaş"
                    aria-label="Paylaş">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
            </button>

            @if ($videoUrl)
                <button type="button"
                        class="sf-gallery__action-btn sf-gallery__action-btn--video"
                        data-sf-video-open
                        data-video-src="{{ $videoUrl }}"
                        title="Videoya bax"
                        aria-label="Videoya bax">
                    <i class="fa-solid fa-play"></i>
                </button>
            @endif
        </div>

        <!-- Arrows -->
        <button type="button" class="sf-gallery__arrow sf-gallery__arrow--prev" data-sf-gallery-prev aria-label="Əvvəlki şəkil">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <!-- Main Image -->
        <button type="button" class="sf-gallery__main" data-sf-gallery-open aria-label="Şəkli böyüt">
            <img src="{{ $galleryItems[0]['src'] }}" alt="{{ $title }}" data-sf-gallery-image loading="eager">
        </button>

        <button type="button" class="sf-gallery__arrow sf-gallery__arrow--next" data-sf-gallery-next aria-label="Növbəti şəkil">
            <i class="fa-solid fa-chevron-right"></i>
        </button>


        <!-- Dot Pagination (Mobile) -->
        @if (count($galleryItems) > 1)
            <div class="sf-gallery__dots" data-sf-gallery-dots>
                @foreach ($galleryItems as $index => $item)
                    <span class="sf-gallery__dot @if ($index === 0) is-active @endif"
                          data-index="{{ $index }}"
                          data-color-id="{{ $item['color_id'] ?? '' }}"
                          data-size-id="{{ $item['size_id'] ?? '' }}"></span>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Desktop Thumbnails -->
    @if (count($galleryItems) > 1)
        <div class="sf-gallery__thumbs" role="tablist">
            @foreach ($galleryItems as $index => $item)
                <button type="button"
                        class="sf-gallery__thumb @if ($index === 0) is-active @endif"
                        data-sf-gallery-thumb
                        data-src="{{ $item['src'] }}"
                        data-color-id="{{ $item['color_id'] ?? '' }}"
                        data-size-id="{{ $item['size_id'] ?? '' }}"
                        aria-label="Şəkil {{ $index + 1 }}">
                    <img src="{{ $item['src'] }}" alt="" loading="lazy">
                </button>
            @endforeach
        </div>
    @endif

    <div class="sf-lightbox" data-sf-lightbox hidden>
        <button type="button" class="sf-lightbox__close" data-sf-lightbox-close aria-label="Bağla">
            <i class="fa-regular fa-xmark"></i>
        </button>

        @if (count($galleryItems) > 1)
            <button type="button" class="sf-lightbox__arrow sf-lightbox__arrow--prev" data-sf-lightbox-prev aria-label="Əvvəlki şəkil">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        @endif

        <div class="sf-lightbox__stage" data-sf-lightbox-stage>
            <img src="{{ $galleryItems[0]['src'] }}" alt="{{ $title }}" data-sf-lightbox-image>
        </div>

        @if (count($galleryItems) > 1)
            <button type="button" class="sf-lightbox__arrow sf-lightbox__arrow--next" data-sf-lightbox-next aria-label="Növbəti şəkil">
                <i class="fa-solid fa-chevron-right"></i>
            </button>

            <div class="sf-lightbox__footer">
                <div class="sf-lightbox__counter">
                    <span data-sf-lightbox-current>1</span> / <span data-sf-lightbox-total>{{ count($galleryItems) }}</span>
                </div>

                <div class="sf-lightbox__thumbs" role="tablist">
                    @foreach ($galleryItems as $index => $item)
                        <button type="button"
                                class="sf-lightbox__thumb @if ($index === 0) is-active @endif"
                                data-sf-lightbox-thumb
                                data-index="{{ $index }}"
                                data-color-id="{{ $item['color_id'] ?? '' }}"
                                data-size-id="{{ $item['size_id'] ?? '' }}"
                                aria-label="Şəkil {{ $index + 1 }}">
                            <img src="{{ $item['src'] }}" alt="" loading="lazy">
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if ($videoUrl)
        <div class="sf-modal sf-video-modal" id="sf-video-modal" hidden>
            <button type="button" class="sf-modal__backdrop" data-sf-modal-close aria-label="Bağla"></button>
            <div class="sf-modal__dialog sf-video-modal__dialog">
                <button type="button" class="sf-modal__close" data-sf-modal-close aria-label="Bağla">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <video controls playsinline preload="metadata" data-sf-video-player src="{{ $videoUrl }}"></video>
            </div>
        </div>
    @endif
</div>

