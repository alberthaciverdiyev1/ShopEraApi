<script lang="ts">
	import { onMount } from 'svelte';
	import BestSellerCard from '$lib/components/cards/BestSellerCard.svelte';
	import { fetchBanners, bannerHref, type ApiBanner } from '$lib/services/banners';
	import { fetchProducts, productImage, type ApiProduct } from '$lib/services/products';
	import { translate } from '$lib/i18n';

	type TabKey = 'latest' | 'popular' | 'sale';

	const tabs: { key: TabKey; label: string; target: string }[] = [
		{ key: 'latest', label: 'Latest', target: 'pills-home' },
		{ key: 'popular', label: 'Popular', target: 'pills-profile' },
		{ key: 'sale', label: 'On-sale', target: 'pills-contact' }
	];

	let {
		latest: serverLatest = [],
		popular: serverPopular = [],
		onSale: serverOnSale = [],
		middleBanners: serverMiddleBanners = []
	}: {
		latest?: ApiProduct[];
		popular?: ApiProduct[];
		onSale?: ApiProduct[];
		middleBanners?: ApiBanner[];
	} = $props();

	let active = $state<TabKey>('latest');

	let clientLatest = $state<ApiProduct[] | null>(null);
	let clientPopular = $state<ApiProduct[] | null>(null);
	let clientOnSale = $state<ApiProduct[] | null>(null);
	let clientMiddleBanner = $state<ApiBanner | null | undefined>(undefined);
	let loading = $state(false);

	const latest = $derived(clientLatest ?? serverLatest);
	const popular = $derived(clientPopular ?? serverPopular);
	const onSale = $derived(clientOnSale ?? serverOnSale);
	const middleBanner = $derived(clientMiddleBanner ?? serverMiddleBanners.find((banner) => banner.image || banner.product) ?? null);
	const lists = $derived<Record<TabKey, ApiProduct[]>>({
		latest,
		popular,
		sale: onSale
	});

	const availableTabs = $derived(tabs.filter((tab) => (lists[tab.key]?.length ?? 0) > 0));

	$effect(() => {
		if (availableTabs.length > 0 && !availableTabs.some((t) => t.key === active)) {
			active = availableTabs[0].key;
		}
	});

	const hasContent = $derived((availableTabs.length > 0 || !!middleBanner) && !loading);

	onMount(async () => {
		loading = latest.length === 0 && popular.length === 0 && onSale.length === 0 && !middleBanner;
		if (!loading) return;
		try {
			const [latestProducts, popularProducts, saleProducts, middleBanners] = await Promise.all([
				fetchProducts({ order_by: 'created_at', order_type: 'desc', per_page: 8 }),
				fetchProducts({ order_by: 'sales_count', order_type: 'desc', per_page: 8 }),
				fetchProducts({ discount: 1, order_by: 'created_at', order_type: 'desc', per_page: 8 }),
				fetchBanners('middle')
			]);

			clientLatest = latestProducts;
			clientPopular = popularProducts;
			clientOnSale = saleProducts;
			clientMiddleBanner = middleBanners.find((banner) => banner.image || banner.product) ?? null;
		} catch (error) {
			console.error('Failed to load best sellers', error);
		} finally {
			loading = false;
		}
	});
</script>

{#if hasContent}
	<!-- Best Seller Section -->
	<section class="best-seller-section section-padding fix bg-1">
		<div class="container">
			{#if availableTabs.length > 0}
				<div class="section-top-wrapper">
					<div class="row gy-3 align-items-center">
						<div class="col-lg-4">
							<div class="section-title">
								<h2 class="title">{$translate('Best Sellers')}</h2>
							</div>
						</div>
						{#if availableTabs.length > 1}
							<div class="col-lg-8 d-flex justify-content-lg-end">
								<div class="best-seller-tab-btn-wrapper">
									<ul class="nav nav-pills" id="pills-tab" role="tablist">
										{#each availableTabs as tab (tab.key)}
											<li class="nav-item" role="presentation">
												<button class="nav-link" class:active={active === tab.key}
														id="{tab.target}-tab" type="button" role="tab"
														aria-controls={tab.target} aria-selected={active === tab.key}
														onclick={() => (active = tab.key)}>{$translate(tab.label)}
												</button>
											</li>
										{/each}
									</ul>
								</div>
							</div>
						{/if}
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<div class="tab-content" id="pills-tabContent">
							{#each availableTabs as tab (tab.key)}
								<div class="tab-pane fade" class:show={active === tab.key} class:active={active === tab.key}
									 id={tab.target} role="tabpanel" aria-labelledby="{tab.target}-tab" tabindex="0">
									<div class="best-seller-tab-content-wrapper">
										<div class="row g-4">
											{#each lists[tab.key] as product (product.id)}
												<div class="col-xl-3 col-md-4 col-6">
													<BestSellerCard {product} />
												</div>
											{/each}
										</div>
									</div>
								</div>
							{/each}
						</div>
					</div>
				</div>
			{/if}
			{#if middleBanner}
			<div class="feature-banner" class:mt-4={availableTabs.length > 0}>
				<div class="feature-copy">
					<h3>{middleBanner.product?.title || $translate('Selected offer')}</h3>
					<p>{$translate('Discover a highlighted product selected for the homepage campaign.')}</p>
				</div>
				<div class="feature-media">
					<img src={middleBanner.image || (middleBanner.product ? productImage(middleBanner.product) : '/assets/images/video/drone.png')} alt={middleBanner.product?.title || 'Featured product'} loading="lazy">
					<a class="theme-btn style3 feature-action" href={bannerHref(middleBanner)}>
						{middleBanner.product ? $translate('View product') : $translate('Shop now')}</a>
				</div>
			</div>
			{/if}
		</div>
	</section>
{/if}

<style>
	.section-top-wrapper {
		margin-bottom: 34px;
	}

	.section-title {
		margin: 0;
	}

	.section-title :global(.title) {
		margin: 0;
	}

	.best-seller-tab-btn-wrapper {
		width: min(100%, 372px);
	}

	.best-seller-tab-btn-wrapper :global(.nav-pills) {
		display: grid;
		grid-auto-flow: column;
		grid-auto-columns: 1fr;
		align-items: center;
		gap: 8px;
		width: 100%;
		padding: 6px;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 999px;
		background: #ffffff;
		box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
	}

	.best-seller-tab-btn-wrapper :global(.nav-link) {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 100%;
		min-height: 38px;
		padding: 0 14px;
		border-radius: 999px;
		color: #334155;
		font-size: 14px;
		font-weight: 600;
		line-height: 1;
		white-space: nowrap;
	}

	.best-seller-tab-btn-wrapper :global(.nav-link.active) {
		background: var(--theme);
		color: #ffffff;
		box-shadow: 0 8px 18px rgba(var(--theme-rgb), 0.18);
	}

	.feature-banner {
		display: grid;
		grid-template-columns: minmax(0, 0.9fr) minmax(360px, 1.1fr);
		align-items: center;
		gap: 42px;
		margin-top: 64px;
		padding: 52px;
		border-radius: 34px;
		background:
			linear-gradient(135deg, rgba(8, 17, 31, 0.92), rgba(19, 42, 65, 0.9)),
			linear-gradient(135deg, #0a111e, #12324c);
		overflow: hidden;
	}

	.feature-copy {
		color: #fff;
	}

	.feature-copy h3 {
		max-width: 560px;
		margin: 0;
		color: #fff;
		font-size: clamp(32px, 4vw, 56px);
		line-height: 1.02;
	}

	.feature-copy p {
		max-width: 420px;
		margin: 18px 0 28px;
		color: rgba(255, 255, 255, 0.74);
		font-size: 16px;
		line-height: 1.6;
	}

	.feature-media {
		position: relative;
		display: block;
		aspect-ratio: 1.35 / 1;
		border-radius: 30px;
		overflow: hidden;
		background: rgba(255, 255, 255, 0.08);
		box-shadow: 0 28px 70px rgba(0, 0, 0, 0.28);
	}

	.feature-media img {
		width: 100%;
		height: 100%;
		display: block;
		object-fit: cover;
	}

	.feature-action {
		position: absolute;
		left: 50%;
		bottom: 22px;
		z-index: 2;
		transform: translateX(-50%);
		box-shadow: 0 14px 34px rgba(3, 19, 35, 0.28);
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one) {
		display: block;
		height: 100%;
		min-height: 0;
		padding: 10px;
		border-radius: 14px;
		background: #ffffff;
		border: 1px solid rgba(15, 23, 42, 0.08);
		overflow: hidden;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__thumb) {
		width: 100%;
		height: auto;
		margin: 0 0 10px;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__thumb a) {
		display: block;
		aspect-ratio: 1 / 1;
		border-radius: 12px;
		overflow: hidden;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__thumb img) {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__content) {
		padding: 0;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__content-title) {
		margin-bottom: 5px;
		font-size: 14px;
		line-height: 1.25;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__star-wrap) {
		gap: 4px;
		align-items: center;
		margin-bottom: 6px;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__star-wrap .star) {
		font-size: 11px;
		line-height: 1;
		white-space: nowrap;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__star-wrap span) {
		font-size: 11px;
		white-space: nowrap;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__content-price) {
		font-size: 13px;
		line-height: 1.25;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__icons) {
		top: 16px;
		right: 16px;
		gap: 6px;
	}

	.best-seller-tab-content-wrapper :global(.best-seller-one__icons button) {
		width: 32px;
		height: 32px;
		font-size: 13px;
	}

	@media (max-width: 991.98px) {
		.feature-banner {
			grid-template-columns: 1fr;
			gap: 30px;
			padding: 36px;
		}

		.feature-copy {
			text-align: center;
		}

		.feature-copy h3,
		.feature-copy p {
			margin-left: auto;
			margin-right: auto;
		}
	}

	@media (max-width: 575.98px) {
		.best-seller-section {
			padding-top: 26px;
		}

		.section-top-wrapper {
			margin-bottom: 6px;
		}

		.section-top-wrapper :global(.row) {
			--bs-gutter-y: 4px;
		}

		.section-title {
			margin-bottom: 0;
		}

		.section-title :global(.title) {
			font-size: 30px;
			line-height: 1.15;
			margin-bottom: 0;
		}

		.best-seller-tab-btn-wrapper :global(.nav-pills) {
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 7px;
			padding: 6px;
			border-radius: 18px;
		}

		.best-seller-tab-btn-wrapper,
		.best-seller-tab-btn-wrapper :global(.nav-item) {
			width: 100%;
		}

		.best-seller-tab-btn-wrapper :global(.nav-link) {
			min-height: 36px;
			padding: 0 8px;
			font-size: 12px;
		}

		.best-seller-tab-content-wrapper :global(.row) {
			--bs-gutter-x: 12px;
			--bs-gutter-y: 12px;
		}

		.feature-banner {
			gap: 18px;
			margin-top: 28px;
			padding: 18px;
			border-radius: 20px;
		}

		.feature-copy h3 {
			font-size: 26px;
			line-height: 1.08;
		}

		.feature-copy p {
			display: -webkit-box;
			overflow: hidden;
			max-width: 300px;
			margin: 10px auto 16px;
			font-size: 13px;
			line-height: 1.45;
			-webkit-box-orient: vertical;
			-webkit-line-clamp: 2;
		}

		.feature-action {
			bottom: 14px;
			display: inline-flex !important;
			align-items: center !important;
			justify-content: center !important;
			height: 40px;
			min-height: 40px;
			padding: 0 18px !important;
			font-size: 13px !important;
			font-weight: 700 !important;
			line-height: 1 !important;
			box-sizing: border-box;
		}

		.feature-media {
			width: min(100%, 360px);
			justify-self: center;
			aspect-ratio: 1.15 / 1;
			border-radius: 18px;
		}
	}

	@media (max-width: 420px) {
		.feature-banner {
			padding: 16px;
		}

		.feature-copy h3 {
			font-size: 24px;
		}

		.feature-media {
			width: min(100%, 310px);
		}
	}
</style>
