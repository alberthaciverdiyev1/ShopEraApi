@props([
    'categories' => [],
    'wishlist' => [],
    'user' => null,
])

@php
    $path = '/'.ltrim(request()->path(), '/');
    $items = [
        ['href' => '/', 'label' => 'Ana səhifə', 'icon' => 'fa-solid fa-house', 'active' => $path === '/'],
        ['href' => '/categories', 'label' => 'Kataloq', 'icon' => 'fa-solid fa-grid-2', 'active' => str_starts_with($path, '/categories')],
        ['href' => '/wishlist', 'label' => 'İstək siyahısı', 'icon' => 'fa-solid fa-heart', 'active' => str_starts_with($path, '/wishlist')],
    ];
    $accountActive = str_starts_with($path, '/settings') || str_starts_with($path, '/login') || str_starts_with($path, '/register') || str_starts_with($path, '/dashboard');
@endphp

<nav class="mobile-bottom-nav" data-sf-bottom-nav aria-label="Mobil menyu">
    @foreach ($items as $item)
        <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'active' : '' }}" aria-label="{{ $item['label'] }}">
            <span class="icon-wrap">
                <i class="{{ $item['icon'] }}"></i>
            </span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach

    <button type="button" class="{{ $accountActive ? 'active' : '' }}" aria-label="Menyu və Hesab"
            aria-expanded="false" aria-controls="mobile-account-drawer" data-sf-account-drawer-open>
        <span class="icon-wrap">
            <i class="fa-solid fa-bars"></i>
        </span>
        <span>Menyu</span>
    </button>
</nav>

<div id="mobile-account-drawer" class="sf-account-drawer sf-trendyol-drawer" role="dialog" aria-modal="true" aria-label="Menyu və Hesab" hidden>
    <button type="button" class="drawer-backdrop" aria-label="Bağla" data-sf-account-drawer-close></button>

    <div class="account-drawer trendyol-drawer-body" data-sf-drawer-container>
        {{-- 1. Drawer Header --}}
        <div class="trendyol-drawer-header">
            <a href="/" class="drawer-logo" aria-label="ShopEra">
                <img src="/assets/images/logo/logo.svg?v=20261002" alt="ShopEra" class="drawer-logo-img">
            </a>
            <button type="button" class="drawer-close-btn" aria-label="Bağla" data-sf-account-drawer-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- 2. Account summary --}}
        <div class="trendyol-account">
            @auth
                <a href="/dashboard" class="trendyol-account__profile">
                    <span class="trendyol-account__avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name ?: 'İ', 0, 1)) }}</span>
                    <span class="trendyol-account__meta">
                        <small>Salam,</small>
                        <strong>{{ auth()->user()->name ?? 'İstifadəçi' }}</strong>
                    </span>
                    <i class="fa-regular fa-chevron-right"></i>
                </a>
            @else
                <div class="trendyol-account__guest">
                    <span class="trendyol-account__avatar trendyol-account__avatar--guest">
                        <i class="fa-regular fa-user"></i>
                    </span>
                    <span class="trendyol-account__meta">
                        <strong>Hesabınıza giriş edin</strong>
                        <small>Sifarişlər, favoritlər və daha çox</small>
                    </span>
                    <a href="{{ route('storefront.login') }}" class="trendyol-account__login">Giriş</a>
                </div>
            @endauth
        </div>

        {{-- 3. Bottom actions and utility links --}}
        <div class="trendyol-drawer-footer">
            <a href="/elan/ver" class="trendyol-cta">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Yeni elan yerləşdir</span>
            </a>

            @if (auth()->check())
                <div class="trendyol-footer-group">
                    <span class="trendyol-footer-title">Hesab</span>
                    <div class="trendyol-footer-row">
                        <a href="/dashboard" class="trendyol-footer-chip">
                            <i class="fa-regular fa-receipt"></i>
                            <span>Sifarişlərim</span>
                        </a>
                        <a href="/dashboard?tab=messages" class="trendyol-footer-chip">
                            <i class="fa-regular fa-comment-dots"></i>
                            <span>Mesajlar</span>
                        </a>
                        <a href="/elanlarim" class="trendyol-footer-chip">
                            <i class="fa-regular fa-rectangle-list"></i>
                            <span>Elanlarım</span>
                        </a>
                    </div>
                </div>
            @endif

            <div class="trendyol-footer-group">
                <span class="trendyol-footer-title">Kəşf et</span>
                <div class="trendyol-footer-links">
                    <a href="/shop" class="trendyol-footer-link">
                        <span class="icon-wrap"><i class="fa-regular fa-bag-shopping"></i></span>
                        <span class="link-label">Bütün elanlar</span>
                        <i class="fa-regular fa-chevron-right link-arrow"></i>
                    </a>
                    <a href="/magazalar" class="trendyol-footer-link">
                        <span class="icon-wrap"><i class="fa-regular fa-store"></i></span>
                        <span class="link-label">Mağazalar</span>
                        <i class="fa-regular fa-chevron-right link-arrow"></i>
                    </a>
                    <a href="/about" class="trendyol-footer-link">
                        <span class="icon-wrap"><i class="fa-regular fa-circle-info"></i></span>
                        <span class="link-label">Haqqımızda</span>
                        <i class="fa-regular fa-chevron-right link-arrow"></i>
                    </a>
                    <a href="/contact" class="trendyol-footer-link">
                        <span class="icon-wrap"><i class="fa-regular fa-comments"></i></span>
                        <span class="link-label">Bizimlə əlaqə</span>
                        <i class="fa-regular fa-chevron-right link-arrow"></i>
                    </a>
                    @auth
                        <form action="{{ route('storefront.logout') }}" method="POST" class="trendyol-logout-form">
                            @csrf
                            <button type="submit" class="trendyol-footer-link trendyol-footer-link--logout">
                                <span class="icon-wrap"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="link-label">Çıxış</span>
                                <i class="fa-regular fa-chevron-right link-arrow"></i>
                            </button>
                        </form>
                    @endauth
                </div>
            </div>

            <p class="trendyol-footer-note">
                <i class="fa-solid fa-shield-halved"></i>
                Təhlükəsiz alış-veriş · ShopEra
            </p>
        </div>
    </div>
</div>
