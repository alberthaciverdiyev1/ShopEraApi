<script lang="ts">
	import { translate } from '$lib/i18n';
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
	let { data } = $props();
</script>

<svelte:head>
	<title>Snaker — {$translate('Home')}</title>
	<meta name="description" content="Snaker — multipurpose ecommerce" />
</svelte:head>

{#if data.features?.stories !== false}
	<StoryReel />
{/if}
{#if data.features?.banners !== false}
	<HeroSection banners={data.heroBanners ?? []} />
{/if}
<MarqueeSection blocks={data.promoBlocks ?? []} />
<CategorySection />
<BestSellerSection
	latest={data.latestProducts ?? []}
	popular={data.popularProducts ?? []}
	onSale={data.saleProducts ?? []}
	middleBanners={data.features?.banners !== false ? data.middleBanners ?? [] : []}
/>
<FeaturedProductSection products={data.featuredProducts ?? []} />
{#if data.features?.banners !== false}
	<PromoSection banners={data.promoBanners ?? []} />
{/if}
{#if data.features?.reviews !== false}
	<TestimonialSection featured={data.featuredReviews} />
{/if}
<PromoBlocksSection blocks={data.promoBlocks ?? []} />

{#if data.features?.blog !== false && (data.recentBlogs?.length ?? 0) > 0}
	<BlogSection blogs={data.recentBlogs ?? []} />
{:else}
	<div class="home-footer-spacer" aria-hidden="true"></div>
{/if}
<PopupModal />

<style>
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
