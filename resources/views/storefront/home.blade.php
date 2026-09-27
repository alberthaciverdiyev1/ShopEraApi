@extends('storefront.layout')

@push('head')
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => 'Teymur Store',
            'url' => route('storefront.home'),
            'image' => $meta['image'] ?? '',
            'address' => $settings?->address ?? 'Baku',
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <style>
        /* ── Hero Banner Slider ── */
        .hero-band {
            background: linear-gradient(180deg, #8ec7f4 0%, #eef7ff 60%, #fff 100%);
            padding: 48px 0 52px;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }
        .hero-slider-wrap {
            position: relative;
            border-radius: 28px;
            overflow: hidden;
            aspect-ratio: 16 / 7;
            background: #d9d9df;
            box-shadow: 0 24px 56px rgba(22,21,35,.13);
        }
        .hero-slider-wrap img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity .5s ease;
        }
        .hero-slider-wrap img.active { opacity: 1; }
        .slider-dots {
            position: absolute;
            bottom: 14px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 7px;
            z-index: 2;
        }
        .slider-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,.5);
            cursor: pointer;
            transition: background .3s, width .3s;
            border: 0;
            padding: 0;
        }
        .slider-dot.active {
            background: #fff;
            width: 22px;
            border-radius: 4px;
        }

        @media (max-width: 960px) {
            .hero-grid { grid-template-columns: 1fr; }
            .hero-slider-wrap { aspect-ratio: 16 / 6; }
        }
    </style>
@endpush

@section('content')
    <section class="hero-band">
        <div class="shell hero-grid">
            <div class="hero-copy">
                <h1>Teymur Store</h1>
                <p>Mobil tətbiqdəki məhsullara, kateqoriyalara və endirimlərə veb üzərindən rahat bax. Sifariş prosesi tətbiqdə davam edir.</p>
                <div class="hero-actions">
                    <a class="pill-button" href="{{ route('storefront.products') }}">Məhsullara bax</a>
                    <a class="ghost-button js-download-trigger" href="#" data-download-source="hero">Tətbiqi yüklə</a>
                </div>
            </div>

            @if($banners->isNotEmpty())
                <div class="hero-slider-wrap" id="heroSlider">
                    @foreach($banners as $i => $banner)
                        <img
                            src="{{ $banner->image }}"
                            alt="Teymur Store kampaniya {{ $i + 1 }}"
                            class="{{ $i === 0 ? 'active' : '' }}"
                        >
                    @endforeach
                    <div class="slider-dots">
                        @foreach($banners as $i => $banner)
                            <button class="slider-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}" aria-label="Slide {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="shell">
            <div class="section-head">
                <div>
                    <h2>Kateqoriyalar</h2>
                    <p>Mobil tətbiqdəki ritmə uyğun kəşf bölməsi.</p>
                </div>
                <a class="ghost-button" href="{{ route('storefront.categories') }}">Hamısı</a>
            </div>

            <div class="category-grid">
                @foreach($categories->take(8) as $category)
                    @php
                        $name = is_array($category->name)
                            ? ($category->name[app()->getLocale()] ?? $category->name['az'] ?? reset($category->name))
                            : $category->name;
                        $name = \Illuminate\Support\Str::ucfirst(trim((string) $name));
                    @endphp
                    <a class="category-card" href="{{ route('storefront.products', ['category' => $category->id]) }}">
                        @if($category->image)
                            <img class="category-thumb" src="{{ $category->image }}" alt="{{ $name }}">
                        @endif
                        <div>
                            <h3>{{ $name }}</h3>
                            <span>Kəşf et →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section">
        <div class="shell">
            <div class="section-head">
                <div>
                    <h2>Fleş Endirim</h2>
                    <p>Endirimli məhsullara bax, sifarişi tətbiqdə tamamla.</p>
                </div>
                <a class="ghost-button" href="{{ route('storefront.products') }}">Hamısına bax</a>
            </div>
            <div class="product-grid">
                @foreach($featuredProducts as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    <section class="section">
        <div class="shell">
            <div class="section-head">
                <div>
                    <h2>Digər baxılan məhsullar</h2>
                    <p>Baxış sayına görə seçilmiş məhsullar.</p>
                </div>
            </div>
            <div class="product-grid">
                @foreach($recommendedProducts as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    @if(($listingCards ?? collect())->isNotEmpty())
        @include('storefront.listings.partials.style')
        <section class="section lst-ink">
            <div class="shell">
                <div class="section-head">
                    <div>
                        <h2>Elanlar</h2>
                        <p>Nəqliyyat, daşınmaz əmlak və digər bölmələr.</p>
                    </div>
                    <a class="ghost-button" href="{{ route('storefront.listings') }}">Hamısına bax</a>
                </div>
                <div class="lst-grid">
                    @foreach($listingCards as $card)
                        @include('storefront.listings.partials.card', ['card' => $card])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <script>
        (function () {
            const wrap = document.getElementById('heroSlider');
            if (!wrap) return;
            const imgs = wrap.querySelectorAll('img');
            const dots = wrap.querySelectorAll('.slider-dot');
            if (imgs.length < 2) return;
            let current = 0;

            function go(n) {
                imgs[current].classList.remove('active');
                dots[current].classList.remove('active');
                current = (n + imgs.length) % imgs.length;
                imgs[current].classList.add('active');
                dots[current].classList.add('active');
            }

            dots.forEach(dot => dot.addEventListener('click', () => go(+dot.dataset.index)));
            setInterval(() => go(current + 1), 4000);
        })();
    </script>
@endsection
