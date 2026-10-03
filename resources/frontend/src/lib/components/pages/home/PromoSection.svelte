<script lang="ts">
	import { onMount } from 'svelte';
	import { fetchBanners, bannerHref, type ApiBanner } from '$lib/services/banners';
	import { productImage } from '$lib/services/products';
	import { translate } from '$lib/i18n';

	let { banners: serverBanners = [] }: { banners?: ApiBanner[] } = $props();
	let clientBanners = $state<ApiBanner[] | null>(null);
	const banners = $derived(clientBanners ?? serverBanners);

	onMount(async () => {
		if (banners.length > 0) return;
		try {
			clientBanners = await fetchBanners('small');
		} catch (error) {
			console.error('Failed to load promo banners', error);
		}
	});
</script>

{#if banners.length > 0}
	<section class="promo-section">
		<div class="container">
			<div class="promo-grid">
				{#each banners.slice(0, 2) as banner, index (banner.id)}
					<a class:wide={index === 1} class="promo-tile" href={bannerHref(banner)}>
						<img
							src={banner.image || (banner.product ? productImage(banner.product) : '/assets/images/offer/promo1.png')}
							alt={banner.product?.title || 'Promo'}
							loading="lazy"
						/>
						<span class="eyebrow">{$translate('Limited offer')}</span>
						<strong>{banner.product?.title || banner.title || $translate('Selected offer')}</strong>
						<small>{banner.subtitle || $translate('Shop now')}</small>
					</a>
				{/each}
			</div>
		</div>
	</section>
{/if}

<style>
	.promo-section {
		padding: 0 0 72px;
	}

	.promo-grid {
		display: grid;
		grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
		gap: 24px;
	}

	.promo-tile {
		position: relative;
		display: flex;
		min-height: 310px;
		padding: 34px;
		flex-direction: column;
		justify-content: flex-end;
		border-radius: 28px;
		overflow: hidden;
		color: #fff;
		text-decoration: none;
		background: #08111f;
		isolation: isolate;
	}

	.promo-tile::after {
		content: '';
		position: absolute;
		inset: 0;
		z-index: -1;
		background: linear-gradient(180deg, rgba(8, 17, 31, 0.08), rgba(8, 17, 31, 0.78));
	}

	.promo-tile img {
		position: absolute;
		inset: 0;
		z-index: -2;
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: transform 0.28s ease;
	}

	.promo-tile:hover img {
		transform: scale(1.04);
	}

	.eyebrow {
		margin-bottom: 10px;
		font-size: 12px;
		font-weight: 800;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}

	.promo-tile strong {
		max-width: 430px;
		font-size: clamp(26px, 3vw, 40px);
		line-height: 1.05;
	}

	.promo-tile small {
		margin-top: 18px;
		font-size: 14px;
		font-weight: 800;
		text-transform: uppercase;
	}

	@media (max-width: 991.98px) {
		.promo-grid {
			grid-template-columns: 1fr;
		}

		.promo-tile {
			min-height: 280px;
		}
	}

	@media (max-width: 575.98px) {
		.promo-section {
			padding-bottom: 48px;
		}

		.promo-tile {
			min-height: 230px;
			padding: 24px;
			border-radius: 24px;
		}
	}
</style>
