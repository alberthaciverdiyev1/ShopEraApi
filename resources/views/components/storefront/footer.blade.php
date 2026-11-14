@props([])

<footer class="sf-footer">
    <div class="container">
        <div class="sf-footer__grid">
            <div class="sf-footer__brand">
                <a href="/" class="sf-footer__logo">
                    <img src="/assets/images/logo/logo.svg?v=20261002" alt="ShopEra">
                </a>
                <p>Alqı-satqı elanları, mağazalar və gündəlik fürsətlər bir yerdə.</p>
                <div class="sf-footer__social">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a>
                </div>
            </div>

            @php
                $footerGroups = $footerGroups ?? [];
                $footerSections = $footerSections ?? [];
            @endphp

            @if (! empty($footerGroups))
                {{-- Links managed in the admin panel (Parametrlər → Footer linkləri). --}}
                @foreach ($footerSections as $sectionKey => $sectionLabel)
                    @continue(empty($footerGroups[$sectionKey]))

                    <div class="sf-footer__col">
                        <h2>{{ $sectionLabel }}</h2>
                        @foreach ($footerGroups[$sectionKey] as $link)
                            <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                @endforeach
            @else
                {{-- Fallback so a fresh install still shows the familiar columns. --}}
                <div class="sf-footer__col">
                    <h2>Kataloq</h2>
                    <a href="/shop">Bütün elanlar</a>
                    <a href="/categories">Kateqoriyalar</a>
                    <a href="/blog">Bloq</a>
                </div>

                <div class="sf-footer__col">
                    <h2>İnformasiya</h2>
                    <a href="/about">Haqqımızda</a>
                    <a href="/privacy">Məxfilik siyasəti</a>
                    <a href="/terms">İstifadə şərtləri</a>
                    <a href="/faq">Tez-tez verilən suallar</a>
                </div>

                <div class="sf-footer__col">
                    <h2>Əlaqə</h2>
                    <a href="tel:+994500000000">+994 50 000 00 00</a>
                    <a href="mailto:info@shopera.az">info@shopera.az</a>
                    <a href="/contact">Əlaqə forması</a>
                </div>
            @endif
        </div>

        <div class="sf-footer__bottom">
            <span>© {{ date('Y') }} ShopEra. Bütün hüquqlar qorunur.</span>
        </div>
    </div>
</footer>
