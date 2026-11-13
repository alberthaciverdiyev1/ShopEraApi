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

    <button type="button" class="{{ $accountActive ? 'active' : '' }}" aria-label="Hesab"
            aria-expanded="false" aria-controls="mobile-account-drawer" data-sf-account-drawer-open>
        <span class="icon-wrap">
            <i class="fa-solid fa-bars"></i>
        </span>
        <span>Hesab</span>
    </button>
</nav>

<div id="mobile-account-drawer" class="sf-account-drawer" role="dialog" aria-modal="true" aria-label="Hesab" hidden>
    <button type="button" class="drawer-backdrop" aria-label="Bağla" data-sf-account-drawer-close></button>

    <div class="account-drawer">
        <div class="drawer-handle"></div>

        <div class="drawer-header">
            <div>
                <span>Hesab</span>
                <strong>
                    @auth
                        {{ auth()->user()->name ?? 'Hesabım' }}
                    @else
                        Qonaq hesab
                    @endauth
                </strong>
            </div>
            <button type="button" aria-label="Bağla" data-sf-account-drawer-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="drawer-pages" aria-label="Menyu">
            <a href="/" class="{{ $path === '/' ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i>
                <span>Ana səhifə</span>
            </a>
            <a href="/shop" class="{{ str_starts_with($path, '/shop') ? 'active' : '' }}">
                <i class="fa-solid fa-store"></i>
                <span>Elanlar</span>
            </a>
            <a href="/categories" class="{{ str_starts_with($path, '/categories') ? 'active' : '' }}">
                <i class="fa-solid fa-grid-2"></i>
                <span>Kateqoriyalar</span>
            </a>
            <a href="/magazalar" class="{{ str_starts_with($path, '/magazalar') || str_starts_with($path, '/magaza/') && ! str_starts_with($path, '/magaza/panel') ? 'active' : '' }}">
                <i class="fa-solid fa-shop"></i>
                <span>Mağazalar</span>
            </a>
            <a href="/about" class="{{ str_starts_with($path, '/about') ? 'active' : '' }}">
                <i class="fa-solid fa-circle-info"></i>
                <span>Haqqımızda</span>
            </a>
            <a href="/contact" class="{{ str_starts_with($path, '/contact') ? 'active' : '' }}">
                <i class="fa-solid fa-headset"></i>
                <span>Əlaqə</span>
            </a>
        </nav>

        <div class="drawer-actions">
            @auth
                <a href="/dashboard">
                    <i class="fa-solid fa-user"></i>
                    <span>Hesabım</span>
                </a>
                <a href="/dashboard">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Sifarişlər</span>
                </a>
                <a href="/dashboard?tab=messages">
                    <i class="fa-regular fa-comment-dots"></i>
                    <span>Mesajlar</span>
                </a>
                <a href="/settings">
                    <i class="fa-solid fa-gear"></i>
                    <span>Parametrlər</span>
                </a>
                <a href="/elan/ver">
                    <i class="fa-regular fa-plus"></i>
                    <span>Yeni elan</span>
                </a>
            @else
                <a href="{{ route('storefront.login') }}" class="primary-action">
                    <i class="fa-solid fa-user"></i>
                    <span>Giriş</span>
                </a>
            @endauth

            <a href="{{ route('storefront.cart') }}">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Səbət</span>
            </a>
            <a href="/wishlist">
                <i class="fa-solid fa-heart"></i>
                <span>İstək siyahısı</span>
            </a>
        </div>

        @auth
            <div class="drawer-actions drawer-danger">
                <form action="{{ route('storefront.logout') }}" method="POST">
                    @csrf
                    <button type="submit">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Çıxış</span>
                    </button>
                </form>
            </div>
        @endauth
    </div>
</div>
