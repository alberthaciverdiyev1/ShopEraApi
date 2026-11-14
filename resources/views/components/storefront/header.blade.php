@php
    $navLinks = [
        ['href' => '/', 'label' => 'Ana səhifə'],
        ['href' => '/shop', 'label' => 'Elanlar'],
        ['href' => '/magazalar', 'label' => 'Mağazalar'],
        ['href' => '/about', 'label' => 'Haqqımızda'],
        ['href' => '/blog', 'label' => 'Bloq'],
        ['href' => '/contact', 'label' => 'Əlaqə'],
    ];

    $isActive = fn (string $href): bool => $href === '/'
        ? request()->path() === '/'
        : request()->is(ltrim($href, '/').'*');

@endphp

<header class="header-section-1">
    <div id="header-sticky" class="header-1">
        {{-- Mobile Navbar (Trendyol style: Hamburger + Logo on left, Search + User + Heart + Cart on right) --}}
        <div class="sf-mobile-navbar">
            <div class="sf-mobile-navbar__inner">
                <div class="sf-mobile-navbar__left">
                    <button type="button" class="sf-mobile-nav-btn sf-mobile-nav-btn--menu"
                            data-sf-account-drawer-open
                            aria-label="Menyu"
                            aria-expanded="false">
                        <i class="fa-solid fa-bars"></i>
                    </button>

                    <a href="/" class="sf-mobile-logo" aria-label="ShopEra">
                        <img src="/assets/images/logo/logo.svg?v=20261002" alt="ShopEra" class="sf-mobile-logo-img">
                    </a>
                </div>

                <div class="sf-mobile-navbar__right">
                    <button type="button" class="sf-mobile-nav-btn sf-mobile-nav-btn--search"
                            data-sf-mobile-search-toggle
                            aria-label="Axtarış"
                            aria-expanded="false">
                        <i class="fa-regular fa-magnifying-glass"></i>
                    </button>

                    @auth
                        <a href="/dashboard" class="sf-mobile-nav-btn" aria-label="Hesabım">
                            <i class="fa-regular fa-user"></i>
                        </a>
                    @else
                        <a href="{{ route('storefront.login') }}" class="sf-mobile-nav-btn" aria-label="Giriş">
                            <i class="fa-regular fa-user"></i>
                        </a>
                    @endauth

                    <a href="/wishlist" class="sf-mobile-nav-btn" aria-label="İstək siyahısı">
                        <i class="fa-regular fa-heart"></i>
                        @if (count($wishlist))
                            <span class="sf-mobile-nav-badge">{{ count($wishlist) }}</span>
                        @endif
                    </a>

                    <a href="{{ route('storefront.cart') }}" class="sf-mobile-nav-btn" aria-label="Səbət">
                        <i class="fa-regular fa-cart-shopping"></i>
                    </a>
                </div>
            </div>

            {{-- Collapsible mobile search bar --}}
            <div class="sf-mobile-search-pane" data-sf-mobile-search-panel hidden>
                <x-storefront.navbar-search />
            </div>
        </div>

        {{-- Desktop Top Bar --}}
        <div class="header-top-one">
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

            @if (! empty($socialLinks ?? []))
                <nav class="header-social" aria-label="Sosial şəbəkələr">
                    @foreach ($socialLinks as $link)
                        <a href="{{ $link['url'] }}" aria-label="{{ $link['label'] }}"
                           target="_blank" rel="noopener noreferrer">
                            <i class="{{ $link['icon'] }}"></i>
                        </a>
                    @endforeach
                </nav>
            @endif

            <div class="lang">
                <div class="user">
                    @auth
                        <a href="/dashboard" class="d-inline-flex align-items-center top-account-link">
                            @if ($user?->avatar)
                                <img src="{{ $user->avatar }}" alt="{{ $user->name ?? 'User' }}" class="header-avatar me-2">
                            @else
                                <i class="fa-solid fa-user"></i>
                            @endif
                            <span class="top-account-name">{{ $user->name ?? 'Hesabım' }}</span>
                        </a>
                        <a href="/wishlist" class="top-wishlist-link" aria-label="İstək siyahısı">
                            <i class="fa-regular fa-heart"></i>
                            @if (count($wishlist))
                                <span class="top-wishlist-count">{{ count($wishlist) }}</span>
                            @endif
                        </a>
                        <a href="/magaza/panel" class="top-store-link">Mağazam</a>
                    @else
                        <a href="{{ route('storefront.login') }}" class="top-account-link">
                            <i class="fa-solid fa-user"></i>
                            Giriş
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        {{-- Desktop Main Header --}}
        <div class="container-fluid header-main-container">
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

                        {{-- Support chat is switched off (see live-chat.blade.php), so
                             its dashboard tab is not rendered either; the icon is
                             kept here for when the chat comes back. --}}
                        @if (false)
                            @auth
                                <a href="/dashboard?tab=messages" class="header-chat-icon" aria-label="Mesajlar">
                                    <i class="fa-regular fa-comment-dots"></i>
                                </a>
                            @else
                                <a href="{{ route('storefront.login') }}" class="header-chat-icon" aria-label="Mesajlar">
                                    <i class="fa-regular fa-comment-dots"></i>
                                </a>
                            @endauth
                        @endif

                        <div class="menu-wishlist menu-cart">
                            <div class="cart-box wishlist-box">
                                @if (count($wishlist))
                                    <div class="sf-wishlist-mini">
                                        @foreach ($wishlist as $item)
                                            @php
                                                $image = collect($item['images'] ?? [])->pluck('image_path')->filter()->first()
                                                    ?? '/assets/images/top-deals-item/topDealsItemThumb1_1.png';
                                            @endphp
                                            <a class="sf-wishlist-mini__item" href="{{ ! empty($item['slug']) ? '/elan/'.$item['slug'] : '/shop' }}">
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

                        <a href="/elan/ver" class="header-new-listing" aria-label="Yeni elan">
                            <i class="fa-regular fa-plus"></i>
                            <span>Yeni elan</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
