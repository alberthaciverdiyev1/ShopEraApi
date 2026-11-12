@php
    $categories = app(\App\Services\Storefront\CatalogService::class)->categories();
@endphp

@php
    $navLinks = [
        ['href' => '/', 'label' => 'Ana səhifə'],
        ['href' => '/shop', 'label' => 'Elanlar'],
        ['href' => '/about', 'label' => 'Haqqımızda'],
        ['href' => '/blog', 'label' => 'Bloq'],
        ['href' => '/contact', 'label' => 'Əlaqə'],
    ];

    $isActive = fn (string $href): bool => $href === '/'
        ? request()->path() === '/'
        : request()->is(ltrim($href, '/').'*');

    $user = \Illuminate\Support\Facades\Auth::user();
    $wishlist = $user
        ? array_slice(app(\App\Services\Storefront\WishlistService::class)->products(), 0, 4)
        : [];
@endphp

<header class="header-section-1">
    <div id="header-sticky" class="header-1">
        <div class="header-top-one">
            <div class="phone-icon">
                <i class="icon-telephone"></i>
                <a href="tel:+994500000000">+994 50 000 00 00</a>
            </div>

            <nav class="top-nav-links" aria-label="Əsas menyu">
                <ul>
                    @foreach ($navLinks as $link)
                        <li class="{{ $isActive($link['href']) ? 'active' : '' }}">
                            <a href="{{ $link['href'] }}" class="{{ $isActive($link['href']) ? 'active' : '' }}">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="lang">
                <div class="user">
                    @auth
                        <a href="/dashboard" class="d-inline-flex align-items-center top-account-link">
                            @if ($user?->avatar)
                                <img src="{{ $user->avatar }}" alt="{{ $user->name ?? 'User' }}" class="header-avatar me-2">
                            @else
                                <i class="fa-solid fa-user"></i>
                            @endif
                            {{ $user->name ?? 'Hesabım' }}
                        </a>
                        <a href="/magaza/panel">Mağazam</a>
                        <form action="{{ route('storefront.logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button class="user-logout" type="submit">Çıxış</button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="mega-menu-wrapper">
                <div class="header-main">
                    <div class="header-left">
                        <div class="logo">
                            <a href="/" class="header-logo">
                                <img src="/assets/images/logo/logo.svg?v=20261002" alt="ShopEra" class="header-logo-img">
                            </a>
                        </div>

                        <div class="header-cataegory-item" data-sf-catalog>
                            <ul class="header-cataegory">
                                <li>
                                    <a href="/shop" aria-expanded="false" data-sf-catalog-toggle>
                                        <span class="left-icon"><i class="icon-app"></i></span>
                                        Bütün kateqoriyalar
                                        <span class="right-icon"><i class="fa-regular fa-chevron-down"></i></span>
                                    </a>
                                </li>
                            </ul>

                            <x-storefront.catalog-menu :categories="$categories" />
                        </div>
                    </div>

                    <div class="header-right d-flex justify-content-end align-items-center">
                        <div class="header-search-shell">
                            <x-storefront.navbar-search />
                        </div>

                        @auth
                            <a href="/dashboard?tab=messages" class="header-chat-icon" aria-label="Mesajlar">
                                <i class="fa-regular fa-comment-dots"></i>
                            </a>
                        @else
                            <a href="{{ route('storefront.login') }}" class="header-chat-icon" aria-label="Mesajlar">
                                <i class="fa-regular fa-comment-dots"></i>
                            </a>
                        @endauth

                        <div class="menu-wishlist menu-cart">
                            <div class="cart-box wishlist-box">
                                @if (count($wishlist))
                                    <div class="sf-wishlist-mini">
                                        @foreach ($wishlist as $item)
                                            @php
                                                $image = collect($item['images'] ?? [])->pluck('image_path')->filter()->first()
                                                    ?? '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
                                            @endphp
                                            <a class="sf-wishlist-mini__item" href="/elan/{{ $item['slug'] ?? $item['id'] }}">
                                                <img src="{{ $image }}" alt="" loading="lazy">
                                                <span>
                                                    <strong>{{ $item['title']['az'] ?? '' }}</strong>
                                                    <em>{{ number_format((float) ($item['discount'] ?? $item['price'] ?? 0), 2) }} ₼</em>
                                                </span>
                                            </a>
                                        @endforeach
                                        <a href="/wishlist" class="theme-btn sf-wishlist-mini__all">Hamısına bax</a>
                                    </div>
                                @else
                                    <div class="shopping-items text-center py-4">
                                        <p class="text-muted mb-3" style="font-size: 14px;">İstək siyahınız boşdur.</p>
                                        <a href="/shop" class="theme-btn" style="padding: 10px 20px; font-size: 13px;">Elanlara bax</a>
                                    </div>
                                @endif
                            </div>
                            <a class="cart-icon wishlist-icon" href="/wishlist" aria-label="İstək siyahısı">
                                <i class="fa-regular fa-heart"></i>
                            </a>
                        </div>

                        <a href="/elan/ver" class="header-new-listing">
                            <i class="fa-regular fa-plus"></i>
                            Yeni elan
                        </a>

                        @auth
                            <a href="/dashboard" class="header-login-btn">
                                @if ($user?->avatar)
                                    <img src="{{ $user->avatar }}" alt="{{ $user->name ?? 'User' }}" class="header-avatar">
                                @else
                                    <i class="fa-regular fa-user"></i>
                                @endif
                                <span>{{ $user->name ?? 'Hesabım' }}</span>
                            </a>
                        @else
                            <a href="{{ route('storefront.login') }}" class="header-login-btn">Giriş</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
