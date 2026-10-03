<script lang="ts">
    import {onMount} from 'svelte';
    import {fetchBanners, bannerHref, type ApiBanner} from '$lib/services/banners';
    import {price, productImage, productTitle} from '$lib/services/products';
    import {translate} from '$lib/i18n';

    let {banners: serverBanners = []}: { banners?: ApiBanner[] } = $props();
    let clientBanners = $state<ApiBanner[] | null>(null);
    let activeIndex = $state(0);

    const banners = $derived(clientBanners ?? serverBanners);
    const slides = $derived(banners.filter((banner) => banner.image || banner.product));
    const activeBanner = $derived(slides[activeIndex] ?? slides[0]);
    const activeProduct = $derived(activeBanner?.product ?? null);
    const activeTitle = $derived(
        activeProduct ? productTitle(activeProduct) : (activeBanner?.title || $translate('Selected offer'))
    );
    const activeDescription = $derived(
        activeProduct?.description || activeBanner?.subtitle
        || $translate('Selected quality products, fair prices, and easy shopping in one place.')
    );
    const activeImage = $derived(activeBanner ? activeBanner.image || (activeProduct ? productImage(activeProduct) : '') : '');

    onMount(async () => {
        if (banners.length > 0) return;
        try {
            clientBanners = await fetchBanners('big');
        } catch (error) {
            console.error('Failed to load hero banners', error);
        }
    });
</script>

{#if slides.length > 0 && activeBanner && activeImage}
    <section class="hero-section">
        <div class="container">
            <div class="hero-shell">
                <div class="hero-copy">
                    <h1>{activeTitle}</h1>
                    <p>{activeDescription}</p>

                    {#if activeProduct}
                        <div class="hero-meta">
                            <div class="meta-item">
                                <span>{$translate('Price')}</span>
                                <strong>${price(activeProduct).toFixed(2)}</strong>
                            </div>
                            {#if (activeProduct.colors?.length ?? 0) > 0}
                                <div class="meta-item">
                                    <span>{$translate('Colors')}</span>
                                    <div class="color-plate">
                                        {#each activeProduct.colors?.slice(0, 4) ?? [] as color (color.id)}
                                            <i class="color-dot"
                                               style={`background-color: ${color.hex || '#0A111E'}`}></i>
                                        {/each}
                                    </div>
                                </div>
                            {/if}
                        </div>
                    {/if}

                    <div class="hero-actions">
                        <a class="theme-btn style6 hero-btn" href={bannerHref(activeBanner)}>
                            {activeProduct ? $translate('View product') : $translate('Shop now')}
                        </a>
                        <a class="hero-link" href="/shop">{$translate('Explore shop')}</a>
                    </div>
                </div>

                <div class="hero-media-wrap">
                    <a class="hero-media" href={bannerHref(activeBanner)}>
                        <img
                            src={activeImage}
                            alt={activeTitle}
                            loading="eager"
                        />
                    </a>

                    {#if slides.length > 1}
                        <div class="hero-picks" aria-label={$translate('Featured banners')}>
                            {#each slides.slice(0, 3) as banner, index (banner.id)}
                                {@const product = banner.product}
                                {@const image = banner.image || (product ? productImage(product) : '')}
                                {#if image}
                                    <button
                                        type="button"
                                        class:active={index === activeIndex}
                                        onclick={() => (activeIndex = index)}
                                        aria-label={$translate('Show {name}', { name: product ? productTitle(product) : $translate('Selected offer') })}
                                    >
                                        <img src={image} alt="" loading="lazy"/>
                                    </button>
                                {/if}
                            {/each}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </section>
{/if}

<style>
    .hero-section {
        padding: 34px 0 72px;
    }

    .hero-shell {
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 0.95fr) minmax(420px, 1.05fr);
        align-items: center;
        gap: 46px;
        min-height: 640px;
        padding: 70px;
        border: 1px solid rgba(10, 17, 30, 0.08);
        border-radius: 44px;
        background: radial-gradient(circle at 80% 18%, rgba(255, 255, 255, 0.86) 0 20%, transparent 42%),
        linear-gradient(
            135deg,
            color-mix(in srgb, var(--theme) 9%, white) 0%,
            color-mix(in srgb, var(--theme) 15%, white) 52%,
            color-mix(in srgb, var(--theme) 5%, white) 100%
        );
        overflow: hidden;
    }

    .hero-shell::after {
        content: '';
        position: absolute;
        inset: auto -80px -150px auto;
        width: 360px;
        height: 360px;
        border-radius: 50%;
        background: rgba(var(--theme-rgb), 0.12);
        pointer-events: none;
    }

    .hero-copy {
        position: relative;
        z-index: 2;
        max-width: 560px;
    }

    h1 {
        margin: 26px 0 22px;
        color: #08111f;
        font-size: clamp(42px, 5vw, 78px);
        font-weight: 700;
        line-height: 0.98;
    }

    p {
        max-width: 460px;
        margin: 0;
        color: rgba(8, 17, 31, 0.72);
        font-size: 17px;
        line-height: 1.65;
    }

    .hero-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        max-width: 420px;
        margin-top: 34px;
    }

    .meta-item {
        min-height: 96px;
        padding: 18px 20px;
        border: 1px solid rgba(10, 17, 30, 0.06);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.76);
        box-shadow: 0 18px 42px rgba(10, 17, 30, 0.08);
    }

    .meta-item span {
        display: block;
        margin-bottom: 8px;
        color: rgba(8, 17, 31, 0.48);
        font-size: 14px;
        font-weight: 600;
    }

    .meta-item strong {
        color: #08111f;
        font-size: 28px;
        line-height: 1;
    }

    .color-plate {
        display: flex;
        align-items: center;
        gap: 9px;
        min-height: 28px;
    }

    .color-dot {
        display: block;
        width: 18px;
        height: 18px;
        border: 1px solid rgba(10, 17, 30, 0.18);
        border-radius: 999px;
    }

    .hero-actions {
        display: flex;
        align-items: center;
        gap: 18px;
        margin-top: 34px;
    }

    .hero-actions :global(.theme-btn),
    .hero-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 48px;
        padding: 0 28px !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        white-space: nowrap;
        text-align: center;
        border-radius: 999px;
    }

    .hero-link {
        display: inline-flex;
        align-items: center;
        color: #08111f;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        line-height: 1;
        transition: color 0.2s ease;
    }

    .hero-link:hover {
        color: var(--theme);
    }

    .hero-media-wrap {
        position: relative;
        z-index: 2;
        justify-self: end;
        width: min(100%, 620px);
    }

    .hero-media {
        display: block;
        aspect-ratio: 1 / 1;
        border-radius: 40%;
        overflow: hidden;
        background: color-mix(in srgb, var(--theme) 12%, white);
        box-shadow: 0 32px 80px rgba(10, 17, 30, 0.16);
    }

    .hero-media img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .hero-picks {
        position: absolute;
        right: 18px;
        bottom: 18px;
        display: flex;
        gap: 10px;
        padding: 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.76);
        box-shadow: 0 14px 36px rgba(10, 17, 30, 0.12);
        backdrop-filter: blur(14px);
    }

    .hero-picks button {
        width: 58px;
        height: 58px;
        padding: 0;
        border: 2px solid transparent;
        border-radius: 50%;
        overflow: hidden;
        background: #fff;
        cursor: pointer;
        transition: border-color 0.18s ease,
        transform 0.18s ease;
    }

    .hero-picks button.active {
        border-color: var(--theme);
        transform: translateY(-2px);
    }

    .hero-picks img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    @media (max-width: 1199.98px) {
        .hero-shell {
            grid-template-columns: minmax(0, 0.92fr) minmax(300px, 0.78fr);
            gap: 30px;
            min-height: 0;
            padding: 42px;
            border-radius: 34px;
        }

        .hero-copy {
            max-width: 460px;
            text-align: left;
            justify-self: start;
        }

        h1 {
            margin: 0 0 18px;
            font-size: clamp(42px, 5vw, 56px);
            line-height: 1;
        }

        p {
            margin-left: 0;
            margin-right: 0;
            font-size: 16px;
            line-height: 1.55;
        }

        .hero-meta {
            max-width: 360px;
            margin-top: 28px;
            margin-left: 0;
            margin-right: 0;
        }

        .meta-item {
            min-height: 88px;
            padding: 16px;
            border-radius: 20px;
        }

        .meta-item strong {
            font-size: 25px;
        }

        .hero-actions {
            justify-content: flex-start;
            margin-top: 28px;
        }

        .hero-media-wrap {
            justify-self: end;
            width: min(100%, 390px);
            transform: none;
        }

        .hero-picks {
            right: 12px;
            bottom: 12px;
            padding: 8px;
        }

        .hero-picks button {
            width: 48px;
            height: 48px;
        }
    }

    @media (max-width: 767.98px) {
        .hero-section {
            padding: 14px 0 36px;
        }

        .hero-shell {
            grid-template-columns: minmax(0, 1fr) minmax(132px, 36vw);
            align-items: center;
            gap: 22px;
            padding: 24px 16px 22px;
            border-radius: 24px;
        }

        .hero-copy {
            max-width: none;
            text-align: left;
            justify-self: stretch;
        }

        h1 {
            margin: 16px 0 12px;
            font-size: clamp(28px, 8vw, 36px);
            line-height: 1.04;
        }

        p {
            display: -webkit-box;
            overflow: hidden;
            margin-left: 0;
            margin-right: 0;
            font-size: 14px;
            line-height: 1.45;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 1;
        }

        .hero-meta {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            max-width: 260px;
            margin-top: 14px;
            margin-left: 0;
            margin-right: 0;
        }

        .meta-item {
            min-height: 44px;
            padding: 7px 9px;
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(10, 17, 30, 0.06);
        }

        .meta-item span {
            margin-bottom: 3px;
            font-size: 10px;
        }

        .meta-item strong {
            font-size: 13px;
        }

        .color-plate {
            gap: 5px;
            min-height: 12px;
        }

        .color-dot {
            width: 10px;
            height: 10px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
            justify-content: flex-start;
        }

        .hero-actions :global(.theme-btn),
        .hero-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: auto;
            min-width: 106px;
            height: 40px;
            min-height: 40px;
            max-height: 40px;
            padding: 0 18px !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            line-height: 1 !important;
            white-space: nowrap;
            text-align: center;
            box-sizing: border-box;
        }

        .hero-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            min-height: 40px;
            padding: 0 6px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .hero-media-wrap {
            justify-self: end;
            width: 100%;
            transform: none;
        }

        .hero-picks {
            position: absolute;
            right: 10px;
            bottom: -6px;
            justify-content: center;
            width: max-content;
            max-width: 100%;
            margin: 0;
            padding: 7px;
        }

        .hero-picks button {
            width: 36px;
            height: 36px;
        }
    }

    @media (max-width: 420px) {
        .hero-shell {
            grid-template-columns: minmax(0, 1fr) minmax(112px, 34vw);
            gap: 12px;
            padding-inline: 14px;
        }

        .hero-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-width: 220px;
        }

        .hero-media-wrap {
            width: 100%;
            margin-bottom: 14px;
        }
    }
</style>
