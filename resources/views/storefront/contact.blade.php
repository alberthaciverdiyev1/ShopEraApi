@extends('storefront.layout')

@push('head')
    <style>
        .contact-hero {
            padding: 44px 0 20px;
            background:
                radial-gradient(circle at top left, rgba(242,106,46,.14), transparent 28%),
                radial-gradient(circle at top right, rgba(91,89,194,.10), transparent 24%),
                #fff;
        }
        .contact-hero-grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 18px;
            align-items: stretch;
        }
        .contact-hero-copy h1 {
            font-size: clamp(30px, 4vw, 56px);
            font-weight: 900;
            line-height: .95;
            letter-spacing: -.03em;
            margin-bottom: 14px;
        }
        .contact-hero-copy p {
            max-width: 540px;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.65;
        }
        .contact-highlight,
        .contact-block,
        .faq-card {
            border-radius: 22px;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: var(--shadow-sm);
        }
        .contact-highlight {
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                radial-gradient(circle at top right, rgba(242,106,46,.12), transparent 26%),
                #fff;
        }
        .contact-highlight strong {
            display: block;
            font-size: 15px;
            color: var(--muted);
            margin-bottom: 10px;
        }
        .contact-highlight a {
            display: inline-block;
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 15px;
            letter-spacing: -.02em;
        }
        .contact-layout {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 18px;
            padding: 24px 0 50px;
        }
        .contact-stack {
            display: grid;
            gap: 18px;
        }
        .contact-block { padding: 24px; }
        .contact-block h2 {
            font-size: 24px;
            font-weight: 850;
            margin-bottom: 16px;
            letter-spacing: -.02em;
        }
        .contact-list {
            display: grid;
            gap: 12px;
        }
        .contact-list-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border-radius: 16px;
            background: var(--soft);
        }
        .contact-list-item small {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .contact-list-item strong,
        .contact-list-item a {
            font-size: 17px;
            font-weight: 800;
            line-height: 1.35;
        }
        .contact-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }
        .contact-action {
            min-height: 130px;
            border-radius: 18px;
            padding: 18px;
            background: linear-gradient(180deg, #fff, #fbfbfd);
            border: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .contact-action strong {
            font-size: 18px;
            line-height: 1.15;
            font-weight: 850;
        }
        .contact-action p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
            margin-top: 8px;
        }
        .contact-socials {
            display: grid;
            gap: 10px;
        }
        .contact-social-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border-radius: 16px;
            background: var(--soft);
            font-weight: 800;
        }
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 18px;
        }
        .faq-card {
            padding: 22px;
            min-height: 190px;
        }
        .faq-card h3 {
            font-size: 22px;
            line-height: 1.15;
            font-weight: 850;
            margin-bottom: 12px;
            letter-spacing: -.02em;
        }
        .faq-card p {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.65;
            display: -webkit-box;
            -webkit-line-clamp: 5;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        @media (max-width: 960px) {
            .contact-hero-grid,
            .contact-layout,
            .faq-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .contact-hero { padding: 26px 0 12px; }
            .contact-hero-copy h1 { font-size: 28px; }
            .contact-hero-copy p { font-size: 14px; }
            .contact-highlight,
            .contact-block,
            .faq-card { border-radius: 18px; }
            .contact-highlight { padding: 18px; }
            .contact-highlight a { font-size: 24px; }
            .contact-block { padding: 18px; }
            .contact-block h2 { font-size: 20px; margin-bottom: 14px; }
            .contact-list-item { padding: 12px 14px; }
            .contact-list-item strong,
            .contact-list-item a { font-size: 15px; }
            .contact-actions { grid-template-columns: 1fr; }
            .contact-action { min-height: 116px; }
            .faq-card { min-height: unset; padding: 18px; }
            .faq-card h3 { font-size: 18px; }
            .faq-card p { font-size: 14px; -webkit-line-clamp: 6; }
        }
    </style>
@endpush

@section('content')
<section class="contact-hero">
    <div class="shell contact-hero-grid">
        <div class="contact-hero-copy">
            <h1>Əlaqə</h1>
            <p>Sualın varsa, Teymur Store komandası ilə sürətli şəkildə əlaqə saxlaya bilərsən. Məhsullara baxış veb saytında qalır, sifarişin özü isə mobil tətbiqdə tamamlanır.</p>
        </div>
        <div class="contact-highlight">
            <div>
                <strong>Birbaşa əlaqə</strong>
                @if($settings?->phone_number_1)
                    <a href="tel:{{ preg_replace('/\D+/', '', $settings->phone_number_1) }}">{{ $settings->phone_number_1 }}</a>
                @endif
            </div>
            <a class="pill-button" href="{{ $settings?->google_map_url ?: '#' }}" rel="noopener nofollow">Xəritədə bax</a>
        </div>
    </div>
</section>

<section class="shell contact-layout">
    <div class="contact-stack">
        <div class="contact-block">
            <h2>Əlaqə məlumatları</h2>
            <div class="contact-list">
                @if($settings?->phone_number_1)
                    <div class="contact-list-item">
                        <div>
                            <small>Telefon</small>
                            <a href="tel:{{ preg_replace('/\D+/', '', $settings->phone_number_1) }}">{{ $settings->phone_number_1 }}</a>
                        </div>
                    </div>
                @endif
                @if($settings?->phone_number_2)
                    <div class="contact-list-item">
                        <div>
                            <small>Alternativ nömrə</small>
                            <a href="tel:{{ preg_replace('/\D+/', '', $settings->phone_number_2) }}">{{ $settings->phone_number_2 }}</a>
                        </div>
                    </div>
                @endif
                @if($settings?->whatsapp_number)
                    <div class="contact-list-item">
                        <div>
                            <small>WhatsApp</small>
                            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $settings->whatsapp_number) }}" rel="noopener nofollow">{{ $settings->whatsapp_number }}</a>
                        </div>
                    </div>
                @endif
                @if($settings?->address)
                    <div class="contact-list-item">
                        <div>
                            <small>Ünvan</small>
                            <strong>{{ $settings->address }}</strong>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($faqs->isNotEmpty())
            <div class="contact-block">
                <h2>Tez-tez verilən suallar</h2>
                <div class="faq-grid">
                    @foreach($faqs as $faq)
                        @php
                            $title = is_array($faq->title) ? ($faq->title[app()->getLocale()] ?? $faq->title['az'] ?? reset($faq->title)) : $faq->title;
                            $description = is_array($faq->description) ? ($faq->description[app()->getLocale()] ?? $faq->description['az'] ?? reset($faq->description)) : $faq->description;
                        @endphp
                        <article class="faq-card">
                            <h3>{{ \Illuminate\Support\Str::ucfirst(trim((string) $title)) }}</h3>
                            <p>{{ $description }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="contact-stack">
        <div class="contact-block">
            <h2>Sosial şəbəkələr</h2>
            <div class="contact-socials">
                @if($settings?->instagram_url)
                    <a class="contact-social-link" href="{{ $settings->instagram_url }}" rel="noopener nofollow">
                        <span>Instagram</span>
                        <span>→</span>
                    </a>
                @endif
                @if($settings?->tiktok_url)
                    <a class="contact-social-link" href="{{ $settings->tiktok_url }}" rel="noopener nofollow">
                        <span>TikTok</span>
                        <span>→</span>
                    </a>
                @endif
            </div>

            <div class="contact-actions">
                <div class="contact-action">
                    <div>
                        <strong>Tətbiqdən sifariş et</strong>
                        <p>Səbət, hesab və sifariş izləmə yalnız mobil tətbiqdə aktivdir.</p>
                    </div>
                    <a class="pill-button js-download-trigger" href="#" data-download-source="contact-primary">Tətbiqi yüklə</a>
                </div>
                <div class="contact-action">
                    <div>
                        <strong>Mağazanı xəritədə tap</strong>
                        <p>Ünvana birbaşa bax və naviqasiyanı rahat başlat.</p>
                    </div>
                    <a class="ghost-button" href="{{ $settings?->google_map_url ?: '#' }}" rel="noopener nofollow">Xəritədə bax</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
