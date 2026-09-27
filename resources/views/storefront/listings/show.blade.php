@extends('storefront.layout')
@section('content')
@include('storefront.listings.partials.style')

@php
    $badgeLabels = [
        'price_drop' => ['Qiymət endirilib', 'green'],
        'extract' => ['Çıxarış var', 'green'],
        'no_damage' => ['Vuruğu yoxdur', 'green'],
        'repair' => ['Təmirli', 'blue'],
        'barter' => ['Barter mümkündür', 'blue'],
        'credit' => ['Kredit var', ''],
        'mortgage' => ['İpoteka mümkündür', ''],
        'complex' => ['Yaşayış kompleksi', ''],
    ];
@endphp

<section class="section lst-ink" style="border:0">
    <div class="shell lst-page">

        <div>
            {{-- Qalereya: şəkilə vurmaq linki açmır, səhifənin üstündə
                 böyüdür; sağa-sola keçid və klaviatura da işləyir. --}}
            <div class="lst-main" id="lst-main" @if($photos->isNotEmpty()) data-index="0" @endif>
                @if($photos->isNotEmpty())
                    <img id="lst-main-img" src="{{ $photos->first() }}" alt="{{ $listing->title }}">
                    @if($photos->count() > 1)
                        <button class="lst-nav prev" type="button" data-step="-1" aria-label="Əvvəlki">‹</button>
                        <button class="lst-nav next" type="button" data-step="1" aria-label="Növbəti">›</button>
                        <span class="lst-count"><span id="lst-pos">1</span> / {{ $photos->count() }}</span>
                    @endif
                @else
                    <div style="height:100%;display:flex;align-items:center;justify-content:center;color:#98a2b3">Şəkil yoxdur</div>
                @endif
            </div>

            @if($photos->count() > 1)
                <div class="lst-gallery" id="lst-thumbs" style="margin-top:8px">
                    @foreach($photos as $index => $photo)
                        <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}" data-index="{{ $index }}">
                            <img src="{{ $photo }}" alt="" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif

            @if($video)
                <div class="lst-block" style="margin-top:14px">
                    <h2>Video</h2>
                    <div class="lst-video">
                        {{-- Pleyer öz domenimizdən verilir: YouTube tanımadığı
                             yerdən açılan embed-i qurmur (153 xətası). --}}
                        <iframe src="{{ route('live.player', ['video' => $video]) }}?autoplay=0&controls=1"
                                allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                allowfullscreen loading="lazy" title="Video"></iframe>
                    </div>
                </div>
            @endif
        </div>

        <div>
            <div class="lst-block">
                <p class="lst-hero-price">{{ $priceLabel ?? 'Razılaşma yolu ilə' }}</p>
                <h1 class="lst-hero-title">{{ $listing->title }}</h1>
                @if($summary)<p class="lst-hero-sum">{{ implode(' · ', $summary) }}</p>@endif

                @php($chips = collect($badges)->filter(fn ($key) => isset($badgeLabels[$key])))
                @if($chips->isNotEmpty())
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
                        @foreach($chips as $key)
                            <span class="lst-chip {{ $badgeLabels[$key][1] }}">{{ $badgeLabels[$key][0] }}</span>
                        @endforeach
                    </div>
                @endif

                <p style="margin:14px 0 0;padding-top:12px;border-top:1px solid #f2f4f7;font-size:13px;color:#667085">
                    {{ $listing->views }} baxış · {{ $publishedLabel }}
                </p>

                @if($listing->contact_phone)
                    <div style="margin-top:14px">
                        <a class="lst-call" href="tel:{{ preg_replace('/\s+/', '', $listing->contact_phone) }}">
                            Zəng et · {{ \Modules\Listing\Http\Resources\ListingText::phone($listing->contact_phone) }}
                        </a>
                    </div>
                @endif
            </div>

            @if($fields)
                <div class="lst-block">
                    <h2>Özəlliklər</h2>
                    <dl style="margin:0">
                        @foreach($fields as $field)
                            <div class="lst-spec">
                                <dt>{{ $field['label'] }}</dt>
                                <dd>
                                    @if(is_array($field['display']))
                                        @foreach($field['display'] as $item)<span class="lst-tag">{{ $item }}</span>@endforeach
                                    @elseif($field['type'] === 'boolean')
                                        {{ $field['value'] ? 'Bəli' : 'Xeyr' }}
                                    @else
                                        {{ $field['display'] ?? \Modules\Listing\Http\Resources\ListingText::value($field) }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if($listing->description)
                <div class="lst-block">
                    <h2>Təsvir</h2>
                    <p style="margin:0;font-size:14px;line-height:1.6;color:#002255">{{ $listing->description }}</p>
                </div>
            @endif

            @if($point)
                <div class="lst-block">
                    <h2>Xəritədə yeri</h2>
                    @if($point['label'] ?? null)<p style="margin:-6px 0 10px;font-size:14px;color:#667085">{{ $point['label'] }}</p>@endif
                    <div class="lst-map" id="lst-map"
                         data-lat="{{ $point['lat'] }}" data-lng="{{ $point['lng'] }}"
                         data-tile="{{ $map['tile_url'] }}" data-attribution="{{ $map['attribution'] }}"></div>
                </div>
            @endif

            <div class="lst-block">
                <dl style="margin:0">
                    @if($listing->city || $listing->address)
                        <div class="lst-spec"><dt>Ünvan</dt><dd>{{ collect([$listing->city?->name, $listing->address])->filter()->implode(', ') }}</dd></div>
                    @endif
                    @if($listing->user?->name)
                        <div class="lst-spec"><dt>Satıcı</dt><dd>{{ $listing->user->name }}</dd></div>
                    @endif
                    @if($expiresLabel)
                        <div class="lst-spec"><dt>Elanın bitmə tarixi</dt><dd>{{ $expiresLabel }}</dd></div>
                    @endif
                </dl>
            </div>

            @if($listing->section?->warning_text)
                <div class="lst-warn">{{ $listing->section->warning_text }}</div>
            @endif
        </div>
    </div>
</section>

@if($similar->isNotEmpty())
<section class="section lst-ink">
    <div class="shell">
        <div class="section-head"><h2>Bənzər elanlar</h2></div>
        <div class="lst-grid">
            @foreach($similar as $card)
                @include('storefront.listings.partials.card', ['card' => $card])
            @endforeach
        </div>
    </div>
</section>
@endif

@if($photos->isNotEmpty())
<div class="lst-lightbox" id="lst-lightbox" role="dialog" aria-label="Şəkil">
    <button class="lst-close" type="button" aria-label="Bağla">×</button>
    @if($photos->count() > 1)
        <button class="lst-nav prev" type="button" data-step="-1" aria-label="Əvvəlki">‹</button>
        <button class="lst-nav next" type="button" data-step="1" aria-label="Növbəti">›</button>
        <span class="lst-count"><span id="lst-box-pos">1</span> / {{ $photos->count() }}</span>
    @endif
    <img id="lst-box-img" src="" alt="">
</div>

<script>
    (function () {
        var photos = @json($photos);
        if (!photos.length) return;

        var main = document.getElementById('lst-main');
        var mainImg = document.getElementById('lst-main-img');
        var pos = document.getElementById('lst-pos');
        var thumbs = document.getElementById('lst-thumbs');
        var box = document.getElementById('lst-lightbox');
        var boxImg = document.getElementById('lst-box-img');
        var boxPos = document.getElementById('lst-box-pos');
        var index = 0;

        function show(next) {
            index = (next + photos.length) % photos.length;
            mainImg.src = photos[index];
            if (pos) pos.textContent = index + 1;
            if (boxPos) boxPos.textContent = index + 1;
            if (box.classList.contains('is-open')) boxImg.src = photos[index];

            if (thumbs) {
                Array.prototype.forEach.call(thumbs.children, function (button, at) {
                    button.classList.toggle('is-active', at === index);
                });
            }
        }

        function open() {
            boxImg.src = photos[index];
            box.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function close() {
            box.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        main.addEventListener('click', function (event) {
            var step = event.target.closest('.lst-nav');
            if (step) {
                event.stopPropagation();
                show(index + Number(step.dataset.step));
                return;
            }
            open();
        });

        if (thumbs) {
            thumbs.addEventListener('click', function (event) {
                var button = event.target.closest('button');
                if (button) show(Number(button.dataset.index));
            });
        }

        box.addEventListener('click', function (event) {
            var step = event.target.closest('.lst-nav');
            if (step) return show(index + Number(step.dataset.step));
            if (event.target.closest('.lst-close') || event.target === box) close();
        });

        document.addEventListener('keydown', function (event) {
            if (!box.classList.contains('is-open')) return;
            if (event.key === 'Escape') close();
            if (event.key === 'ArrowLeft') show(index - 1);
            if (event.key === 'ArrowRight') show(index + 1);
        });
    })();
</script>
@endif

@if($point)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<script>
    // Xəritə mənbəyi serverdən gəlir — tətbiqdəki ilə eyni parametr.
    window.addEventListener('load', function () {
        var box = document.getElementById('lst-map');
        if (!box || typeof L === 'undefined') return;

        var lat = parseFloat(box.dataset.lat), lng = parseFloat(box.dataset.lng);
        var map = L.map(box, { scrollWheelZoom: false }).setView([lat, lng], 15);

        L.tileLayer(box.dataset.tile, { attribution: box.dataset.attribution, maxZoom: 19 }).addTo(map);
        L.circleMarker([lat, lng], {
            radius: 9, color: '#fff', weight: 3, fillColor: '#ff6300', fillOpacity: 1,
        }).addTo(map);
    });
</script>
@endif
@endsection
