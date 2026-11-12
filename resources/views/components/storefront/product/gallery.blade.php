@props([
    'images' => [],
    'title' => '',
])

@php
    $images = array_values(array_filter((array) $images));
    $images = $images === [] ? ['/assets/images/top-deals-item/topDealsItemThumb1_1.png'] : $images;
@endphp

<div class="sf-gallery" data-sf-gallery>
    <div class="sf-gallery__stage">
        <button type="button" class="sf-gallery__arrow sf-gallery__arrow--prev" data-sf-gallery-prev aria-label="Əvvəlki şəkil">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <button type="button" class="sf-gallery__main" data-sf-gallery-open aria-label="Şəkli böyüt">
            <img src="{{ $images[0] }}" alt="{{ $title }}" data-sf-gallery-image>
        </button>

        <button type="button" class="sf-gallery__arrow sf-gallery__arrow--next" data-sf-gallery-next aria-label="Növbəti şəkil">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    @if (count($images) > 1)
        <div class="sf-gallery__thumbs" role="tablist">
            @foreach ($images as $index => $image)
                <button type="button"
                        class="sf-gallery__thumb @if ($index === 0) is-active @endif"
                        data-sf-gallery-thumb
                        data-src="{{ $image }}"
                        aria-label="Şəkil {{ $index + 1 }}">
                    <img src="{{ $image }}" alt="" loading="lazy">
                </button>
            @endforeach
        </div>
    @endif

    <div class="sf-lightbox" data-sf-lightbox hidden>
        <button type="button" class="sf-lightbox__close" data-sf-lightbox-close aria-label="Bağla">
            <i class="fa-regular fa-xmark"></i>
        </button>
        <img src="{{ $images[0] }}" alt="{{ $title }}" data-sf-lightbox-image>
    </div>
</div>
