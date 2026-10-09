<script lang="ts">
    import {translate} from '$lib/i18n';
    import CategorySection from '$lib/components/pages/home/CategorySection.svelte';
    import FeaturedProductSection from '$lib/components/pages/home/FeaturedProductSection.svelte';
    import PremiumListingsSection from '$lib/components/pages/home/PremiumListingsSection.svelte';
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

<div class="home-page">
    <CategorySection/>

    {#await data.featuredProducts}
        {@render loader('50vh')}
    {:then featured}
        <FeaturedProductSection products={featured}/>
    {/await}

    {#await data.premiumListings}
        {@render loader('40vh')}
    {:then premium}
        <PremiumListingsSection products={premium}/>
    {/await}
</div>

{#if $features.popups ?? false}
    <PopupModal/>
{/if}

<style>
    :global(.home-page) {
        --home-content-width: 1540px;
    }

    :global(.home-page .container) {
        max-width: var(--home-content-width);
    }

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
</style>
