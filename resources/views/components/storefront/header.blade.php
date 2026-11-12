@props(['categories' => []])

@php
    $roots = collect($categories)->filter(fn ($category) => empty($category['parent_id']));

    // Top-level categories read best in the catalog menu; fall back to
    // whatever came back when the tree is flat.
    $menuCategories = ($roots->isNotEmpty() ? $roots : collect($categories))->take(12);
@endphp

<header class="sf-header">
    <div class="sf-header__top">
        <div class="container-fluid sf-header__top-inner">
            <a class="sf-header__phone" href="tel:+994500000000">
                <i class="icon-telephone"></i>
                <span>+994 50 000 00 00</span>
            </a>

            <nav class="sf-header__links" aria-label="Əsas menyu">
                <a href="/">Ana səhifə</a>
                <a href="/shop">Elanlar</a>
                <a href="/blog">Bloq</a>
                <a href="/about">Haqqımızda</a>
                <a href="/faq">FAQ</a>
                <a href="/contact">Əlaqə</a>
            </nav>

            <div class="sf-header__account">
                <a href="/login">Giriş</a>
                <a href="/register">Qeydiyyat</a>
            </div>
        </div>
    </div>

    <div class="sf-header__main">
        <div class="container-fluid sf-header__main-inner">
            <button type="button" class="sf-header__burger" data-sf-menu-toggle aria-label="Menyu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>

            <a href="/" class="sf-header__logo">
                <img src="/assets/images/logo/logo.svg?v=20261002" alt="ShopEra">
            </a>

            <div class="sf-header__catalog">
                <button type="button" class="sf-header__catalog-toggle" data-sf-catalog-toggle aria-expanded="false">
                    <i class="icon-app"></i>
                    <span>Bütün kateqoriyalar</span>
                    <i class="fa-regular fa-chevron-down"></i>
                </button>

                @if ($menuCategories->isNotEmpty())
                    <div class="sf-header__dropdown" data-sf-catalog-menu hidden>
                        @foreach ($menuCategories as $category)
                            <a href="/shop?category={{ $category['id'] }}">
                                @if (! empty($category['image']))
                                    <img src="{{ $category['image'] }}" alt="" loading="lazy">
                                @endif
                                <span>{{ $category['name'] }}</span>
                            </a>
                        @endforeach
                        <a class="sf-header__dropdown-all" href="/shop">Bütün elanlar →</a>
                    </div>
                @endif
            </div>

            <form class="sf-header__search" action="/shop" method="GET" role="search">
                <input type="search" name="search" placeholder="Elan axtar…" aria-label="Elan axtar">
                <button type="submit" aria-label="Axtar">
                    <i class="fa-regular fa-magnifying-glass"></i>
                </button>
            </form>

            <div class="sf-header__actions">
                <a href="/wishlist" class="sf-header__action" aria-label="Sevimlilər">
                    <i class="fa-regular fa-heart"></i>
                </a>
                <a href="/dashboard" class="sf-header__action" aria-label="Hesabım">
                    <i class="fa-regular fa-user"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="sf-header__mobile" data-sf-mobile-menu hidden>
        <nav>
            <a href="/">Ana səhifə</a>
            <a href="/shop">Elanlar</a>
            <a href="/blog">Bloq</a>
            <a href="/about">Haqqımızda</a>
            <a href="/faq">FAQ</a>
            <a href="/contact">Əlaqə</a>
            <a href="/login">Giriş</a>
            <a href="/register">Qeydiyyat</a>
        </nav>
    </div>
</header>
