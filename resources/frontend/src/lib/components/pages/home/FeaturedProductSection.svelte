<script lang="ts">
	import { onMount } from 'svelte';
	import { translate } from '$lib/i18n';
	import ShopProductCard from '$lib/components/cards/ShopProductCard.svelte';
	import { fetchProducts, type ApiProduct } from '$lib/services/products';

	let { products: serverProducts = [] }: { products?: ApiProduct[] } = $props();
	let clientProducts = $state<ApiProduct[] | null>(null);
	const products = $derived(clientProducts ?? serverProducts);

	onMount(async () => {
		if (products.length > 0) return;
		try {
			clientProducts = await fetchProducts({ order_by: 'created_at', order_type: 'desc', per_page: 12 });
		} catch (error) {
			console.error('Failed to load featured products', error);
		}
	});
</script>

{#if products.length > 0}
	<!-- Featured Product Section -->
	<section class="featured-product-section section-padding fix">
		<div class="container">
			<div class="featured-product-wrapper style1">
				<div class="top-deals-wrapper style1 text-center mb-30">
					<div class="section-title">
						<h2 class="title">{$translate('Our featured products')}</h2>
					</div>
				</div>

				<div class="tab-content" id="pills-tabContent2">
					<div class="tab-pane fade show active" id="pills-one" role="tabpanel" aria-labelledby="pills-one-tab">
						<div class="feature-tab-content">
							<div class="row g-4">
								{#each products as product (product.id)}
									<div class="col-xl-3 col-md-4 col-6">
										<ShopProductCard {product} />
									</div>
								{/each}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
{/if}

<style>
	@media (max-width: 575.98px) {
		.featured-product-section {
			padding-top: 44px;
		}

		.feature-tab-content :global(.row) {
			--bs-gutter-x: 12px;
			--bs-gutter-y: 12px;
		}

		.feature-tab-content :global(.best-seller-one) {
			display: block;
			height: 100%;
			min-height: 238px;
			padding: 10px;
			border-radius: 14px;
		}

		.feature-tab-content :global(.best-seller-one__thumb) {
			width: 100%;
			height: auto;
			margin: 0 0 10px;
		}

		.feature-tab-content :global(.best-seller-one__thumb a) {
			display: block;
			aspect-ratio: 1 / 1;
			border-radius: 12px;
			overflow: hidden;
		}

		.feature-tab-content :global(.best-seller-one__thumb img) {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.feature-tab-content :global(.best-seller-one__content) {
			padding: 0;
		}

		.feature-tab-content :global(.best-seller-one__content-title) {
			margin-bottom: 5px;
			font-size: 14px;
			line-height: 1.25;
		}

		.feature-tab-content :global(.best-seller-one__star-wrap) {
			gap: 4px;
			align-items: center;
			margin-bottom: 6px;
		}

		.feature-tab-content :global(.best-seller-one__star-wrap .star) {
			font-size: 11px;
			line-height: 1;
			white-space: nowrap;
		}

		.feature-tab-content :global(.best-seller-one__star-wrap span) {
			font-size: 11px;
			white-space: nowrap;
		}

		.feature-tab-content :global(.best-seller-one__content-price) {
			font-size: 13px;
			line-height: 1.25;
		}

		.feature-tab-content :global(.best-seller-one__icons) {
			top: 16px;
			right: 16px;
			gap: 6px;
		}

		.feature-tab-content :global(.best-seller-one__icons button) {
			width: 32px;
			height: 32px;
			font-size: 13px;
		}
	}

	@media (max-width: 420px) {
		.feature-tab-content :global(.best-seller-one) {
			min-height: 230px;
		}

		.feature-tab-content :global(.best-seller-one) {
			padding: 9px;
		}
	}
</style>
