<script lang="ts">
    import {translate} from '$lib/i18n';
    import StoryReel from '$lib/components/stories/StoryReel.svelte';
    import HeroSection from '$lib/components/pages/home/HeroSection.svelte';
    import MarqueeSection from '$lib/components/pages/home/MarqueeSection.svelte';
    import CategorySection from '$lib/components/pages/home/CategorySection.svelte';
    import BestSellerSection from '$lib/components/pages/home/BestSellerSection.svelte';
    import FeaturedProductSection from '$lib/components/pages/home/FeaturedProductSection.svelte';
    import PromoSection from '$lib/components/pages/home/PromoSection.svelte';
    import TestimonialSection from '$lib/components/pages/home/TestimonialSection.svelte';
    import BlogSection from '$lib/components/pages/home/BlogSection.svelte';
    import PromoBlocksSection from '$lib/components/pages/home/PromoBlocksSection.svelte';
    import PopupModal from '$lib/components/layout/PopupModal.svelte';
    import {features} from '$lib/services/features';

    let {data} = $props();
</script>

<svelte:head>
    <title>Snaker — {$translate('Home')}</title>
    <meta name="description" content="Snaker — multipurpose ecommerce"/>
</svelte:head>

{#snippet loader(height = '40vh')}
    <div class="home-loader" style="min-height: {height}">
        <span class="home-spinner" aria-hidden="true"></span>
    </div>
{/snippet}

{#await data.features}
    {@render loader('60vh')}
{:then features}
    {#if features.stories ?? false}
        <StoryReel/>
    {/if}

    {#await data.heroBanners}
        {#if true}{@render loader('55vh')}{/if}
    {:then heroBanners}
        {#if true}
            <HeroSection banners={heroBanners}/>
        {/if}
    {/await}

    {#if features.promo_blocks ?? false}
        {#await data.promoBlocks then blocks}
            <MarqueeSection blocks={blocks}/>
        {/await}
    {/if}
    <CategorySection/>

    {#await Promise.all([data.latestProducts, data.popularProducts, data.saleProducts, data.middleBanners])}
        {@render loader('50vh')}
    {:then [latest, popular, sale, middle]}
        <BestSellerSection
            latest={latest}
            popular={popular}
            onSale={sale}
            middleBanners={true ? middle : []}
        />
    {/await}

    {#await data.featuredProducts then featured}
        <FeaturedProductSection products={featured}/>
    {/await}

    {#await data.promoBanners then promo}
        {#if true}
            <PromoSection banners={promo}/>
        {/if}
    {/await}

    {#if features.reviews ?? false}
        {#await data.featuredReviews}
            {@render loader('30vh')}
        {:then featuredReviews}
            <TestimonialSection featured={featuredReviews}/>
        {/await}
    {/if}
    {#if features.promo_blocks ?? false}

        {#await data.promoBlocks then blocks}
            <PromoBlocksSection blocks={blocks}/>
        {/await}
    {/if}
    {#if features.blog ?? false}
        {#await data.recentBlogs then blogs}
            {#if (blogs?.length ?? 0) > 0}
                <BlogSection blogs={blogs}/>
            {:else}
                <div class="home-footer-spacer" aria-hidden="true"></div>
            {/if}
        {/await}
    {:else}
        <div class="home-footer-spacer" aria-hidden="true"></div>
    {/if}
{/await}

{#if $features.popups ?? false}
    <PopupModal/>
{/if}
<style>
    .home-loader {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .home-spinner {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 3px solid var(--border-3, #e2e8f0);
        border-top-color: var(--theme, #06b6d4);
        animation: home-spin 0.8s linear infinite;
    }

    @keyframes home-spin {
        to {
            transform: rotate(360deg);
        }
    }

    .home-footer-spacer {
        height: clamp(64px, 8vw, 112px);
        background: var(--body);
    }

    @media (max-width: 767.98px) {
        .home-footer-spacer {
            height: 56px;
        }
    }
</style>
