<script lang="ts">
    import {onMount} from 'svelte';
    import NavMenu from '$lib/components/layout/NavMenu.svelte';
    import CategoryMenu from '$lib/components/layout/CategoryMenu.svelte';
    import NavbarSearch from '$lib/components/layout/NavbarSearch.svelte';
    import {goto, afterNavigate} from '$app/navigation';
    import {isLoggedIn, logout, user} from '$lib/services/auth';
    import {basketCount, basketItems, basketTotal, removeBasketItem} from '$lib/services/basket';
    import {favoriteProducts, favoritesCount, removeFavorite} from '$lib/services/favorites';
    import {productImage, productTitle, productUrl} from '$lib/services/products';
    import {languageOptions, locale, setLocale, translate, type Locale} from '$lib/i18n';
    import {features} from '$lib/services/features';
    import {chatUnread, refreshChatUnread} from '$lib/services/listing-chat';
    import ChatModal from '$lib/components/chat/ChatModal.svelte';
    import {phoneHref, primaryPhone, settings} from '$lib/services/settings';

    const headerPhone = $derived(primaryPhone($settings));

    // The catalog mega-menu opens on hover (CSS) and toggles on click/tap.
    let catalogOpen = $state(false);
    let catalogItem = $state<HTMLDivElement | null>(null);
    let chatOpen = $state(false);

    // Basket UI is hidden for now (API/services stay).
    const CART_ENABLED = false;

    onMount(() => {
        if ($isLoggedIn) refreshChatUnread();
        const timer = setInterval(() => {
            if ($isLoggedIn) refreshChatUnread();
        }, 30000);
        return () => clearInterval(timer);
    });

    // Close it when navigating away or clicking outside.
    afterNavigate(() => {
        catalogOpen = false;
    });
    $effect(() => {
        if (!catalogOpen) return;
        const onDocClick = (event: MouseEvent) => {
            if (catalogItem && !catalogItem.contains(event.target as Node)) catalogOpen = false;
        };
        document.addEventListener('click', onDocClick);
        return () => document.removeEventListener('click', onDocClick);
    });

    onMount(() => {
        const header = document.getElementById('header-sticky');
        const onScroll = () => header?.classList.toggle('sticky', window.scrollY > 250);
        window.addEventListener('scroll', onScroll, {passive: true});
        onScroll();
        return () => window.removeEventListener('scroll', onScroll);
    });

    async function handleLogout() {
        await logout();
        await goto('/');
    }
</script>

<!-- Header Section -->
<!-- Header Section Start -->
<header class="header-section-1">
    <div id="header-sticky" class="header-1">
        <div class="header-top-one">
            <div class="phone-icon">
                <i class="icon-telephone"></i>
                <a href={phoneHref(headerPhone)}>{headerPhone}</a>
            </div>
            <div class="lang">
                {#if $languageOptions.length > 1}
                    <div class="language">
                        <i class="icon-earth"></i>

                        <div class="form">
                            <select
                                class="single-select w-100"
                                aria-label={$translate('Language')}
                                value={$locale}
                                onchange={(event) => setLocale(event.currentTarget.value as Locale)}
                            >
                                {#each $languageOptions as language (language.value)}
                                    {#if language.status}
                                        <option value={language.value}>{language.label}</option>
                                    {/if}
                                {/each}
                            </select>
                        </div>
                    </div>
                {/if}

                <div class="user">
                    {#if $isLoggedIn}
                        <a href="/dashboard" class="d-inline-flex align-items-center">
                            {#if $user?.avatar}
                                <img src={$user.avatar} alt={$user?.name ?? 'User'} class="header-avatar me-2"/>
                            {:else}
                                <i class="fa-solid fa-user"></i>
                            {/if}
                            {$user?.name ?? $translate('My account')}
                        </a>
                        <a href="/magaza/panel">{$translate('My store')}</a>
                        <button class="user-logout" type="button" onclick={handleLogout}>{$translate('Logout')}</button>
                    {:else}
                        <a href="/login">
                            <i class="fa-solid fa-user"></i>
                            {$translate('Login')}
                        </a>
                    {/if}
                </div>
            </div>

        </div>

        <div class="container-fluid">
            <div class="mega-menu-wrapper">
                <div class="header-main">
                    <div class="header-left">
                        <div class="logo">
                            <a href="/" class="header-logo">
                                <img src={$settings.logo_url || '/assets/images/logo/logo.svg?v=20261002'}
                                     alt="logo-img" class="header-logo-img">
                            </a>
                        </div>
                        <div class="header-cataegory-item" class:catalog-open={catalogOpen} bind:this={catalogItem}>
                            <ul class="header-cataegory">
                                <li>
                                    <a href="/shop" aria-expanded={catalogOpen}
                                       onclick={(event) => { event.preventDefault(); catalogOpen = !catalogOpen; }}>
                                        <span class="left-icon"><i class="icon-app"></i></span>
                                        {$translate('All Categories')}
                                        <span class="right-icon"><i class="fa-regular fa-chevron-down"></i></span>
                                    </a>
                                </li>
                            </ul>
                            <CategoryMenu/>
                        </div>
                    </div>
                    <div class="header-right d-flex justify-content-end align-items-center">
                        <div class="mean__menu-wrapper d-none d-xl-block">
                            <div class="main-menu">
                                <nav id="mobile-menu">
                                    <NavMenu/>
                                </nav>
                            </div>
                        </div>
                        <NavbarSearch/>
                        {#if $isLoggedIn}
                            <button type="button" class="header-chat-icon" aria-label={$translate('Messages')}
                                    onclick={() => (chatOpen = true)}>
                                <i class="fa-regular fa-comment-dots"></i>
                                {#if $chatUnread > 0}<span class="cart-count">{$chatUnread}</span>{/if}
                            </button>
                        {/if}
                        {#if CART_ENABLED}
                        <div class="menu-cart">
                            <div class="cart-box">
                                {#if $basketItems.length}
                                    <ul class="mini-cart-list">
                                        {#each $basketItems.slice(0, 10) as item (item.id)}
                                            <li class="mini-cart-item">
                                                <a href={item.product ? productUrl(item.product) : '/cart'}
                                                   class="mini-cart-thumb">
                                                    <img src={item.product ? productImage(item.product) : ''}
                                                         alt={item.product ? productTitle(item.product) : $translate('Product')}>
                                                </a>
                                                <div class="cart-product">
                                                    <a href={item.product ? productUrl(item.product) : '/cart'}
                                                       class="mini-cart-title">
                                                        {item.product ? productTitle(item.product) : $translate('Product')}
                                                    </a>
                                                    <span
                                                        class="mini-cart-price">{Number(item.retail_total ?? 0).toFixed(2)}
                                                        $ × {item.quantity}</span>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="mini-cart-remove"
                                                    onclick={() => removeBasketItem(item.id)}
                                                    aria-label={$translate('Remove item')}
                                                    title={$translate('Remove item')}
                                                >
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </li>
                                        {/each}
                                    </ul>
                                    <div class="shopping-items d-flex align-items-center justify-content-between">
                                        <span>{$translate('Product')} : {$basketCount}</span>
                                        <span>{$translate('Total')} : ${Number($basketTotal).toFixed(2)}</span>
                                    </div>
                                    <div class="cart-button d-flex justify-content-between mb-4">
                                        <a href="/cart" class="theme-btn btn-view-all">
                                            {$translate('View all')}
                                        </a>
                                        <a href="/checkout" class="theme-btn bg-red-2 btn-checkout-header">
                                            {$translate('Checkout')}
                                        </a>
                                    </div>
                                {:else}
                                    <div class="shopping-items text-center py-4">
                                        <p class="text-muted mb-3"
                                           style="font-size: 14px;">{$translate('Your cart is empty.')}</p>
                                        <a href="/shop" class="theme-btn"
                                           style="padding: 10px 20px; font-size: 13px;">{$translate('Start shopping')}</a>
                                    </div>
                                {/if}
                            </div>
                            <a href="/cart" class="cart-icon" aria-label={$translate('Cart')}>
                                <i class="fa-solid fa-cart-shopping"></i>
                                {#if $isLoggedIn && $basketCount > 0}
                                    <span class="cart-count">{$basketCount}</span>
                                {/if}
                            </a>
                        </div>

                        {/if}
                        <!-- Wishlist (Sevimlilər) Dropdown -->
                        {#if $features.favorites}
                            <div class="menu-wishlist menu-cart">
                                <div class="cart-box wishlist-box">
                                    {#if $favoriteProducts.length}
                                        <ul class="mini-cart-list">
                                            {#each $favoriteProducts.slice(0, 10) as item (item.id)}
                                                <li class="mini-cart-item">
                                                    <a href={productUrl(item)} class="mini-cart-thumb">
                                                        <img src={productImage(item)} alt={productTitle(item)}>
                                                    </a>
                                                    <div class="cart-product">
                                                        <a href={productUrl(item)} class="mini-cart-title">
                                                            {productTitle(item)}
                                                        </a>
                                                        <span class="mini-cart-price">
                                                        ${Number(item.discount || item.price).toFixed(2)}
                                                    </span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        class="mini-cart-remove"
                                                        onclick={() => removeFavorite(item.id)}
                                                        aria-label={$translate('Remove favorite')}
                                                        title={$translate('Remove favorite')}
                                                    >
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </li>
                                            {/each}
                                        </ul>
                                        <div class="shopping-items d-flex align-items-center justify-content-between">
                                            <span>{$translate('Wishlist')} : {$favoritesCount}</span>
                                        </div>
                                        <div class="cart-button mb-4">
                                            <a href="/wishlist" class="theme-btn btn-wishlist-all">
                                                {$translate('View all')}
                                            </a>
                                        </div>
                                    {:else}
                                        <div class="shopping-items text-center py-4">
                                            <p class="text-muted mb-3"
                                               style="font-size: 14px;">{$translate('Your wishlist is empty.')}</p>
                                            <a href="/shop" class="theme-btn"
                                               style="padding: 10px 20px; font-size: 13px;">{$translate('Browse products')}</a>
                                        </div>
                                    {/if}
                                </div>
                                <a class="cart-icon wishlist-icon" href="/wishlist" aria-label={$translate('Wishlist')}>
                                    <i class="fa-regular fa-heart"></i>
                                    {#if $isLoggedIn && $favoritesCount > 0}
                                        <span class="cart-count">{$favoritesCount}</span>
                                    {/if}
                                </a>
                            </div>
                        {/if}

                    </div>
                </div>
            </div>
        </div>
    </div>
    <ChatModal bind:open={chatOpen} />
</header>

<style>
    .header-avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        object-fit: cover;
        vertical-align: middle;
    }

    .user-logout {
        background: none;
        border: 0;
        padding: 0;
        margin-inline-start: 12px;
        color: inherit;
        font: inherit;
        cursor: pointer;
        opacity: 0.75;
    }

    .user-logout:hover {
        opacity: 1;
    }

    :global(.header-top-one) {
        display: grid !important;
        grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr);
        align-items: center !important;
        gap: 18px;
        min-height: 50px;
        padding-top: 7px !important;
        padding-bottom: 7px !important;
        background: #0a111e !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    :global(.header-top-one .phone-icon) {
        width: fit-content;
        min-height: 34px;
        padding: 0 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.03);
    }

    :global(.header-top-one .phone-icon i) {
        color: var(--theme) !important;
    }

    :global(.header-top-one .phone-icon a) {
        color: rgba(255, 255, 255, 0.92) !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        text-transform: none !important;
        letter-spacing: 0 !important;
    }

    :global(.header-top-one .lang) {
        justify-self: end;
        align-items: center;
        gap: 12px;
        margin-left: auto;
    }

    :global(.header-top-one .lang .language) {
        margin: 0 !important;
        padding: 0 16px 0 0 !important;
        border-right: 1px solid rgba(255, 255, 255, 0.16) !important;
    }

    :global(.header-top-one .language) {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
    }

    :global(.header-top-one .language .form) {
        display: flex;
        align-items: center;
        height: 34px;
        min-width: 96px;
    }

    :global(.header-top-one .language .nice-select) {
        display: flex !important;
        align-items: center !important;
        height: 34px !important;
        min-height: 34px !important;
        padding: 0 28px 0 0 !important;
        border: 0 !important;
        background: transparent !important;
        color: var(--white) !important;
        font-size: 13.5px !important;
        line-height: 34px !important;
    }

    :global(.header-top-one .language select.single-select) {
        display: none !important;
    }

    :global(.header-top-one .language .nice-select .current) {
        display: inline-flex;
        align-items: center;
        height: 34px;
        line-height: 34px;
    }

    :global(.header-top-one .language .nice-select::after) {
        top: 50% !important;
        right: 6px !important;
        margin-top: -4px !important;
    }

    :global(.header-top-one .language .nice-select.open .list) {
        top: 100%;
        margin-top: 8px;
        min-width: 168px;
        padding: 6px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.18);
        z-index: 1000;
    }

    :global(.header-top-one .language .nice-select .option) {
        min-height: 34px;
        padding: 0 12px;
        border-radius: 8px;
        color: #0f172a !important;
        font-size: 13.5px;
        font-weight: 600;
        line-height: 34px;
    }

    :global(.header-top-one .language .nice-select .option:hover),
    :global(.header-top-one .language .nice-select .option.focus),
    :global(.header-top-one .language .nice-select .option.selected.focus),
    :global(.header-top-one .language .nice-select .option.selected) {
        background: rgba(var(--theme-rgb), 0.1) !important;
        color: var(--theme) !important;
    }

    :global(.header-top-one .lang .user) {
        display: inline-flex !important;
        align-items: center !important;
        min-height: 34px;
    }

    :global(.header-top-one .lang .user a) {
        display: inline-flex !important;
        align-items: center !important;
        gap: 7px;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.94) !important;
        font-size: 13.5px !important;
        letter-spacing: 0 !important;
    }

    :global(.header-top-one .lang .user a i) {
        margin: 0 !important;
        color: var(--theme);
    }

    :global(.header-1 .header-cataegory-item) {
        margin-left: 22px;
    }

    :global(.header-1 .header-cataegory-item .header-cataegory) {
        min-width: 232px !important;
        padding: 0 18px !important;
        border-color: rgba(15, 23, 42, 0.12) !important;
        background: #ffffff !important;
        box-shadow: none !important;
    }

    :global(.header-1 .header-cataegory > li > a) {
        display: inline-flex !important;
        align-items: center !important;
        gap: 12px;
        min-height: 50px !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        color: #0f172a !important;
        font-size: 15px !important;
        font-weight: 650 !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
        white-space: nowrap !important;
        box-shadow: none !important;
    }

    :global(.header-1 .header-cataegory-item .header-cataegory:hover) {
        border-color: rgba(var(--theme-rgb), 0.45) !important;
        background: color-mix(in srgb, var(--theme) 5%, #fff) !important;
    }

    :global(.header-1 .header-cataegory .left-icon) {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        margin: 0 !important;
        color: var(--theme) !important;
        font-size: 16px !important;
    }

    :global(.header-1 .header-cataegory .right-icon) {
        margin-left: 2px !important;
        color: #0f172a !important;
        font-size: 13px !important;
    }

    :global(.header-1 .header-cataegory-item > .catalog-mega-panel) {
        top: calc(100% + 10px) !important;
        left: 0 !important;
        display: grid !important;
        grid-template-columns: 312px;
        width: min(312px, calc(100vw - 32px)) !important;
        min-width: 0 !important;
        min-height: 560px;
        max-height: min(70vh, 640px);
        overflow: hidden;
        padding: 0 !important;
        border: 1px solid rgba(15, 23, 42, 0.1) !important;
        border-radius: 14px !important;
        background: #ffffff !important;
        box-shadow: 0 22px 52px rgba(15, 23, 42, 0.16) !important;
    }

    :global(.header-1 .header-cataegory-item > .catalog-mega-panel.columns-2) {
        grid-template-columns: 312px 312px;
        width: min(624px, calc(100vw - 32px)) !important;
    }

    :global(.header-1 .header-cataegory-item > .catalog-mega-panel.columns-3) {
        grid-template-columns: 312px 312px 320px;
        width: min(944px, calc(100vw - 32px)) !important;
    }

    :global(.header-1 .header-cataegory-item:hover > .catalog-mega-panel),
    :global(.header-1 .header-cataegory-item.catalog-open > .catalog-mega-panel) {
        visibility: visible !important;
        opacity: 1 !important;
        transform: translateY(0) !important;
    }

    :global(.header-1 .header-cataegory-item > .catalog-mega-panel::before) {
        content: '';
        position: absolute;
        right: 0;
        bottom: 100%;
        left: 0;
        height: 12px;
    }

    :global(.header-1 .catalog-mega-panel .catalog-column) {
        min-width: 0;
        max-height: min(70vh, 640px);
        overflow-y: auto;
        padding: 10px;
        border-right: 1px solid rgba(15, 23, 42, 0.09);
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    :global(.header-1 .catalog-mega-panel .catalog-column:last-child) {
        border-right: 0;
    }

    :global(.header-1 .catalog-mega-panel.columns-1 .catalog-column-primary) {
        border-right: 0;
    }

    :global(.header-1 .catalog-mega-panel .catalog-row) {
        display: flex !important;
        align-items: center !important;
        gap: 12px;
        min-height: 50px;
        padding: 7px 12px !important;
        border: 0 !important;
        border-radius: 8px;
        color: #1f2937 !important;
        font-size: 14.5px !important;
        font-weight: 600 !important;
        line-height: 1.25;
        text-decoration: none !important;
        white-space: normal;
    }

    :global(.header-1 .catalog-mega-panel .catalog-row:hover),
    :global(.header-1 .catalog-mega-panel .catalog-row.active) {
        background: #f5f6fa !important;
        color: var(--theme) !important;
    }

    :global(.header-1 .catalog-mega-panel .catalog-view-all) {
        margin-bottom: 4px;
        border: 1px solid rgba(15, 23, 42, 0.08) !important;
    }

    :global(.header-1 .catalog-mega-panel .catalog-thumb) {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        overflow: hidden;
        border-radius: 8px;
        background: #f1f5f9;
        color: #94a3b8;
    }

    :global(.header-1 .catalog-mega-panel .catalog-thumb img) {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    :global(.header-1 .catalog-mega-panel .catalog-row span:nth-child(2)),
    :global(.header-1 .catalog-mega-panel .catalog-row-text span) {
        min-width: 0;
        flex: 1 1 auto;
    }

    :global(.header-1 .catalog-mega-panel .catalog-chevron) {
        flex: 0 0 auto;
        margin-left: auto;
        color: #94a3b8;
        font-size: 12px;
    }

    :global(.header-1 .catalog-mega-panel .catalog-leaf-link) {
        display: block;
        padding: 11px 10px;
        border-radius: 8px;
        color: #1f2937 !important;
        font-size: 14.5px;
        font-weight: 600;
        line-height: 1.3;
        text-decoration: none !important;
    }

    :global(.header-1 .catalog-mega-panel .catalog-leaf-link:hover),
    :global(.header-1 .catalog-mega-panel .catalog-leaf-link-featured) {
        background: #f5f6fa;
        color: var(--theme) !important;
    }

    :global(.header-1 .catalog-mega-panel .catalog-menu-message) {
        grid-column: 1 / -1;
        padding: 18px;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
    }

    :global(.header-1 .header-main) {
        gap: 22px;
    }

    :global(.header-1 .header-main .header-left),
    :global(.header-1 .header-main .header-right) {
        min-width: 0;
    }

    :global(.header-1 .header-main .header-right) {
        flex: 1 1 auto;
        gap: 20px;
    }

    :global(.header-1 .header-main .header-right .mean__menu-wrapper) {
        margin-right: 20px !important;
        min-width: 0;
    }

    :global(.header-1 .header-main .main-menu ul) {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 18px;
    }

    :global(.header-1 .header-main .main-menu ul li) {
        margin-inline-end: 0 !important;
    }

    :global(.header-1 .header-main .main-menu ul li a) {
        white-space: nowrap;
        letter-spacing: 0 !important;
    }

    @media (min-width: 1200px) and (max-width: 1329.98px) {
        :global(.header-1 .header-cataegory-item) {
            margin-left: 18px;
        }

        :global(.header-1 .header-cataegory-item .header-cataegory) {
            min-width: 218px !important;
            padding: 0 16px !important;
        }

        :global(.header-1 .header-main .main-menu ul) {
            gap: 14px;
        }

        :global(.header-1 .header-main .main-menu ul li a) {
            font-size: 14px !important;
        }

        :global(.header-main .header-right .search-icon),
        :global(.header-right .cart-icon),
        :global(.header-right .wishlist-icon) {
            font-size: 17px !important;
        }
    }

    @media (max-width: 991.98px) {
        :global(.header-top-one) {
            grid-template-columns: 1fr auto;
        }
    }

    @media (max-width: 767.98px) {
        :global(.header-top-one) {
            display: none !important;
        }
    }

    .cart-icon {
        position: relative;
    }

    :global(.header-1 .menu-cart .cart-icon::before),
    :global(.header-top-wrapper .menu-cart .cart-icon::before),
    :global(.header-top-wrapper .menu-cart-items .cart-icon::before),
    :global(.menu-cart .cart-icon::before),
    :global(.cart-icon::before) {
        display: none !important;
        content: none !important;
    }

    .cart-count {
        position: absolute;
        top: -6px;
        inset-inline-end: -8px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9px;
        background: var(--theme);
        color: #fff;
        font-size: 11px;
        line-height: 18px;
        text-align: center;
    }

    /* Mini Cart & Wishlist Dropdown Styles */
    :global(.header-1 .menu-cart .cart-box) {
        width: 320px;
        right: 0 !important;
        left: auto !important;
        border-radius: 12px;
        padding: 12px 18px 0;
    }

    .mini-cart-list {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 380px;
        overflow-y: auto;
    }

    .mini-cart-list::-webkit-scrollbar {
        width: 4px;
    }

    .mini-cart-list::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 4px;
    }

    .btn-view-all,
    .btn-checkout-header {
        white-space: nowrap;
        padding: 10px 16px !important;
        font-size: 13.5px !important;
        line-height: 1.4;
    }

    .btn-wishlist-all {
        display: block;
        width: 100%;
        text-align: center;
        padding: 11px 20px !important;
        font-size: 14px !important;
        line-height: 1.4;
    }

    .mini-cart-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #edf0f5;
    }

    .mini-cart-thumb {
        flex-shrink: 0;
        display: block;
        width: 52px;
        height: 52px;
        border-radius: 8px;
        overflow: hidden;
        background: #f8f9fa;
        border: 1px solid #edf0f5;
    }

    :global(.header-1 .menu-cart .cart-box ul li img),
    :global(.header-1 .menu-cart .cart-box img),
    .mini-cart-thumb img {
        width: 52px !important;
        height: 52px !important;
        min-width: 52px !important;
        max-width: 52px !important;
        object-fit: cover !important;
        border-radius: 8px !important;
        display: block;
    }

    .mini-cart-item .cart-product {
        flex: 1;
        min-width: 0;
    }

    .mini-cart-title {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #1e2532;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
        text-decoration: none;
    }

    .mini-cart-title:hover {
        color: #ef3e2e;
    }

    /* Disable old pseudo-element icon from main.css */
    :global(.header-1 .menu-cart .cart-box ul li a::after) {
        display: none !important;
    }

    .mini-cart-price {
        display: block;
        font-size: 13px !important;
        color: #64748b !important;
        font-weight: 500 !important;
        margin-top: 3px;
    }

    .mini-cart-remove {
        background: none;
        border: none;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 13px;
        cursor: pointer;
        flex-shrink: 0;
        transition: background-color 0.15s, color 0.15s;
        padding: 0;
    }

    .mini-cart-remove:hover {
        background-color: #feebe9;
        color: #ef3e2e;
    }

    @media (max-width: 1199.98px) {
        :global(.header-1 .container-fluid) {
            padding-inline: 8px !important;
        }

        :global(.header-main .header-right .mean__menu-wrapper) {
            display: none !important;
        }

        :global(.header-main .header-right) {
            gap: 12px;
        }

        :global(.header-right .cart-icon i),
        :global(.header-right .wishlist-icon i) {
            font-size: 20px !important;
        }
    }

    .header-logo-img {
        height: 40px;
        width: auto;
        display: block;
    }

    .header-chat-icon {
        position: relative;
        border: 0;
        background: none;
        color: inherit;
        font-size: 20px;
        cursor: pointer;
        padding: 6px 8px;
    }
    .header-chat-icon:hover { color: var(--theme); }
</style>
