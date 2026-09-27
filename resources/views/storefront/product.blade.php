@extends('storefront.layout')

@php
    $title = is_array($product->title)
        ? ($product->title[app()->getLocale()] ?? $product->title['az'] ?? $product->title['en'] ?? reset($product->title))
        : $product->title;
    $title = \Illuminate\Support\Str::ucfirst(trim((string) $title));

    $description = is_array($product->description)
        ? ($product->description[app()->getLocale()] ?? $product->description['az'] ?? $product->description['en'] ?? reset($product->description))
        : $product->description;

    $mainImage = $product->images->first()?->image_path;

    $price = $product->price;
    if ($price === null && $product->sizes->isNotEmpty()) {
        $price = $product->sizes->min(fn ($size) => $size->pivot?->price);
    }
    $discount = $product->discount;
    if ($discount === null && $product->sizes->isNotEmpty()) {
        $discount = $product->sizes->min(fn ($size) => $size->pivot?->discount);
    }

    $price        = (float) ($price ?? 0);
    $discount     = (float) ($discount ?? 0);
    $hasDiscount  = $discount > 0 && $price > $discount;
    $displayPrice = $hasDiscount ? $discount : $price;
    $discountPct  = $hasDiscount ? max(1, round((($price - $discount) / $price) * 100)) : 0;

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $title,
        'description' => \Illuminate\Support\Str::limit(strip_tags($description), 300),
        'image'       => $mainImage,
        'sku'         => $product->sku,
        'offers'      => [
            '@type'         => 'Offer',
            'priceCurrency' => 'AZN',
            'price'         => number_format($displayPrice, 2, '.', ''),
            'availability'  => 'https://schema.org/' . ((int) $product->stock_count > 0 ? 'InStock' : 'OutOfStock'),
        ],
    ];

    $mediaItems = collect();
    foreach ($product->videos as $video) {
        $mediaItems->push([
            'type'  => 'video',
            'src'   => $video->video_path,
            'thumb' => null,
        ]);
    }
    foreach ($product->images as $img) {
        $mediaItems->push([
            'type'  => 'image',
            'src'   => $img->image_path,
            'thumb' => $img->image_path,
        ]);
    }
@endphp

@push('head')
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <style>
        .pd-wrap {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 480px;
            gap: 48px;
            padding: 36px 0 60px;
            align-items: start;
            overflow: hidden;
        }
        .gallery-col { position: sticky; top: 90px; min-width: 0; }
        .gallery-main {
            border-radius: 24px;
            overflow: hidden;
            background: var(--soft);
            aspect-ratio: 1 / 1;
            position: relative;
        }
        .gm-slide {
            position: absolute; inset: 0;
            opacity: 0; pointer-events: none;
            transition: opacity .3s;
        }
        .gm-slide.active { opacity: 1; pointer-events: auto; }
        .gm-slide img, .gm-slide video {
            width: 100%; height: 100%;
            object-fit: contain; display: block;
        }
        .gm-slide video { object-fit: cover; }
        .gallery-badge {
            position: absolute; top: 16px; left: 16px;
            background: var(--coral); color: #fff;
            font-weight: 800; padding: 6px 14px;
            border-radius: var(--pill); z-index: 2;
            pointer-events: none; font-size: 13px;
        }
        .gallery-thumbs {
            display: flex; gap: 10px; margin-top: 12px;
            overflow-x: auto; padding-bottom: 4px;
            scrollbar-width: none; max-width: 100%;
        }
        .gallery-thumbs::-webkit-scrollbar { display: none; }
        .g-thumb {
            flex-shrink: 0; width: 72px; height: 72px;
            border-radius: 12px; overflow: hidden;
            background: var(--soft); border: 2px solid transparent;
            cursor: pointer; transition: border-color .2s;
            position: relative;
        }
        .g-thumb.active { border-color: var(--brand); }
        .g-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .g-thumb-video-icon {
            width: 100%; height: 100%;
            display: grid; place-items: center;
            background: var(--ink); color: #fff; font-size: 24px;
        }
        .detail-col { min-width: 0; overflow-wrap: break-word; }
        .detail-col .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--muted); font-weight: 700; font-size: 15px;
            margin-bottom: 18px; transition: color .15s;
        }
        .detail-col .back-btn:hover { color: var(--ink); }
        .detail-col h1 {
            margin: 0 0 16px;
            font-size: clamp(26px, 3.5vw, 40px);
            line-height: 1.12; letter-spacing: -.02em; font-weight: 900;
        }
        .pd-price-block {
            display: flex; align-items: center; gap: 14px;
            margin: 20px 0 24px; flex-wrap: wrap;
        }
        .pd-price {
            font-size: 38px; font-weight: 900;
            color: var(--brand); line-height: 1; letter-spacing: -.02em;
        }
        .pd-old-price {
            font-size: 20px; color: var(--muted);
            text-decoration: line-through; font-weight: 500;
        }
        .pd-discount-badge {
            background: var(--brand-light); color: var(--brand);
            font-weight: 800; padding: 6px 12px;
            border-radius: var(--pill); font-size: 14px;
        }
        .pd-cta {
            display: flex; gap: 12px; flex-wrap: wrap;
            margin: 24px 0 28px;
        }
        .pd-cta .pill-button {
            flex: 1; min-width: 180px; min-height: 52px; font-size: 16px;
        }
        .pd-cta .ghost-button { min-height: 52px; }
        .pd-divider { border: 0; border-top: 1px solid var(--line); margin: 24px 0; }
        .pd-meta-row {
            display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;
        }
        .pd-label {
            font-weight: 700; font-size: 12px; color: var(--muted);
            text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px;
        }
        .pd-description {
            color: var(--muted); font-size: 16px;
            line-height: 1.78; white-space: pre-line;
            word-break: break-word;
        }

        @media (max-width: 960px) {
            .pd-wrap {
                grid-template-columns: 1fr;
                gap: 24px; padding: 24px 0 40px;
            }
            .gallery-col { position: static; }
            .pd-price { font-size: 30px; }
        }

        @media (max-width: 600px) {
            .pd-wrap { gap: 16px; padding: 16px 0 28px; }
            .gallery-main { border-radius: 16px; }
            .gallery-badge { top: 10px; left: 10px; padding: 4px 10px; font-size: 11px; }
            .gallery-thumbs { gap: 8px; margin-top: 10px; }
            .g-thumb { width: 56px; height: 56px; border-radius: 10px; }
            .detail-col .back-btn { font-size: 13px; margin-bottom: 12px; }
            .detail-col h1 { font-size: 22px !important; line-height: 1.15; }
            .pd-meta-row { gap: 6px; margin-bottom: 14px; }
            .pd-meta-row .stat-pill { padding: 5px 10px; font-size: 12px; }
            .pd-price-block { margin: 14px 0 18px; gap: 10px; }
            .pd-price { font-size: 28px; }
            .pd-old-price { font-size: 16px; }
            .pd-discount-badge { font-size: 12px; padding: 4px 10px; }
            .pd-cta { flex-direction: column; margin: 16px 0 20px; gap: 8px; }
            .pd-cta .pill-button { min-width: unset; min-height: 46px; font-size: 15px; }
            .pd-cta .ghost-button { min-height: 42px; justify-content: center; }
            .pd-divider { margin: 16px 0; }
            .pd-label { font-size: 11px; margin-bottom: 8px; }
            .pd-description { font-size: 14px; line-height: 1.7; }
            .limit-box { padding: 12px 14px; font-size: 13px; border-radius: 14px; }
            .color-dot { width: 24px; height: 24px; border-width: 2px; }
        }
    </style>
@endpush

@section('content')
<div class="shell pd-wrap">

    <div class="gallery-col">
        <div class="gallery-main" id="galleryMain">
            @if($hasDiscount)
                <div class="gallery-badge">-{{ $discountPct }}%</div>
            @endif

            @if($mediaItems->isEmpty())
                <div style="width:100%;height:100%;display:grid;place-items:center;color:var(--muted);font-size:16px">
                    Şəkil yoxdur
                </div>
            @else
                @foreach($mediaItems as $i => $media)
                    <div class="gm-slide {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}">
                        @if($media['type'] === 'video')
                            <video controls playsinline preload="metadata"
                                style="width:100%;height:100%;object-fit:cover">
                                <source src="{{ $media['src'] }}" type="video/mp4">
                            </video>
                        @else
                            <img src="{{ $media['src'] }}" alt="{{ $title }}"
                                loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        @if($mediaItems->count() > 1)
            <div class="gallery-thumbs" id="galleryThumbs">
                @foreach($mediaItems as $i => $media)
                    <div class="g-thumb {{ $i === 0 ? 'active' : '' }}"
                        data-index="{{ $i }}" role="button" tabindex="0"
                        aria-label="Media {{ $i + 1 }}">
                        @if($media['type'] === 'video')
                            <div class="g-thumb-video-icon">▶</div>
                        @else
                            <img src="{{ $media['thumb'] }}" alt="thumb {{ $i + 1 }}" loading="lazy">
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="detail-col">
        <a class="back-btn" href="{{ url()->previous() }}">← Geri</a>

        <h1>{{ $title }}</h1>

        <div class="pd-meta-row">
            <span class="stat-pill">★ {{ number_format((float) ($product->reviews_avg_rate ?? 0), 1) }} ({{ (int) $product->reviews_count }})</span>
            <span class="stat-pill">{{ (int) $product->views }} baxış</span>
            <span class="stat-pill">Stok: {{ (int) $product->stock_count }}</span>
        </div>

        @if($product->purchase_limit)
            <div class="limit-box">
                <strong>Limitli satış — x{{ $product->purchase_limit }} əd</strong>
                <span>Bu məhsula limitli satış tətbiq edilir.</span>
            </div>
        @endif

        @if($product->colors->isNotEmpty())
            <div style="margin-bottom:20px">
                <div class="pd-label">Rəng</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @foreach($product->colors as $color)
                        @php
                            $colorName = is_array($color->name)
                                ? ($color->name[app()->getLocale()] ?? $color->name['az'] ?? reset($color->name))
                                : $color->name;
                        @endphp
                        <span class="color-dot" title="{{ $colorName }}" style="background:{{ $color->hex ?? '#ddd' }}"></span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="pd-price-block">
            <span class="pd-price">{{ number_format($displayPrice, 2) }}₼</span>
            @if($hasDiscount)
                <span class="pd-old-price">{{ number_format($price, 2) }}₼</span>
                <span class="pd-discount-badge">-{{ $discountPct }}% endirim</span>
            @endif
        </div>

        <div class="pd-cta">
            <a class="pill-button js-download-trigger" href="#" data-download-source="product-detail">Sifariş üçün tətbiqi yüklə</a>
            <a class="ghost-button js-download-trigger" href="#" data-download-source="product-detail-secondary">iOS / Android</a>
        </div>

        <hr class="pd-divider">

        <div class="pd-label">Açıqlama</div>
        <div class="pd-description">{{ $description }}</div>
    </div>
</div>

@if($relatedProducts->isNotEmpty())
    <section class="section" style="border-top:1px solid var(--line)">
        <div class="shell">
            <div class="section-head">
                <div>
                    <h2>Oxşar məhsullar</h2>
                    <p>Bu kateqoriyadan digər seçimlər.</p>
                </div>
            </div>
            <div class="product-grid">
                @foreach($relatedProducts as $related)
                    @include('storefront.partials.product-card', ['product' => $related])
                @endforeach
            </div>
        </div>
    </section>
@endif

<script>
(function () {
    var slides = document.querySelectorAll('#galleryMain .gm-slide');
    var thumbs = document.querySelectorAll('#galleryThumbs .g-thumb');
    if (!slides.length) return;

    function go(n) {
        slides.forEach(function(s, i) { s.classList.toggle('active', i === n); });
        thumbs.forEach(function(t, i) { t.classList.toggle('active', i === n); });
        slides.forEach(function(s, i) {
            var v = s.querySelector('video');
            if (v && i !== n) v.pause();
        });
    }

    thumbs.forEach(function(t) {
        t.addEventListener('click', function() { go(+t.dataset.index); });
        t.addEventListener('keydown', function(e) { if (e.key === 'Enter') go(+t.dataset.index); });
    });

    var sx = 0;
    var main = document.getElementById('galleryMain');
    main.addEventListener('touchstart', function(e) { sx = e.touches[0].clientX; }, { passive: true });
    main.addEventListener('touchend', function(e) {
        var dx = e.changedTouches[0].clientX - sx;
        if (Math.abs(dx) < 40) return;
        var cur = Array.from(slides).findIndex(function(s) { return s.classList.contains('active'); });
        go(dx < 0 ? Math.min(cur + 1, slides.length - 1) : Math.max(cur - 1, 0));
    });
})();
</script>
@endsection
