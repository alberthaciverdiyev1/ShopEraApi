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
			clientProducts = await fetchProducts({
				placement: 'premium,vip',
				order_by: 'created_at',
				order_type: 'desc',
				per_page: 10
			});
		} catch (error) {
			console.error('Failed to load premium listings', error);
		}
	});
</script>

{#if products.length > 0}
	<!-- Premium / VIP elanlar (tap.az üslubunda) -->
	<section class="premium-listings-section section-padding fix">
		<div class="container">
			<div class="premium-listings-head">
				<div class="section-title">
					<h2 class="title">{$translate('Premium listings')}</h2>
				</div>
				<a class="latest-ads-link" href="/shop">{$translate('Latest listings')}</a>
			</div>

			<div class="home-product-grid">
				{#each products as product (product.id)}
					<div class="home-product-grid-item">
						<ShopProductCard {product} />
					</div>
				{/each}
			</div>
		</div>
	</section>
{/if}

<style>
	.premium-listings-section {
		padding-top: 44px;
	}

	.premium-listings-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		margin-bottom: 26px;
	}

	.premium-listings-head .section-title {
		margin: 0;
	}

	.premium-listings-head .section-title :global(.title) {
		margin: 0;
	}

	.latest-ads-link {
		flex: none;
		color: var(--theme, #1570ef);
		font-size: 14px;
		font-weight: 600;
		text-decoration: none;
	}

	.latest-ads-link:hover {
		text-decoration: underline;
	}

	.home-product-grid {
		display: grid;
		grid-template-columns: repeat(5, minmax(0, 1fr));
		gap: 13px;
	}

	.home-product-grid-item {
		min-width: 0;
	}

	@media (max-width: 991.98px) {
		.home-product-grid {
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 11px;
		}
	}

	@media (max-width: 575.98px) {
		.premium-listings-section {
			padding-top: 30px;
		}

		.home-product-grid {
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 8px;
		}
	}
</style>
