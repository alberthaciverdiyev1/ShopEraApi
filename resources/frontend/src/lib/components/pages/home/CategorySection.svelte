<script lang="ts">
	import { onMount } from 'svelte';
	import { categories, categoryName, loadCategories } from '$lib/services/categories';
	import { locale, translate } from '$lib/i18n';

	onMount(() => loadCategories());

	// Only the categories flagged 'show on home' (all, if none are flagged).
	const shown = $derived($categories.some((c) => c.show_on_home) ? $categories.filter((c) => c.show_on_home) : $categories);
</script>

{#if shown.length > 0}
	{#if false}
		<!-- Product Section (Popular Categories) -->
		<section class="product-section section-padding fix">
			<div class="product-contianer-wrapper style1">
				<div class="container">
					<div class="row">
						<div class="swiper gt-slider productSliderOne" id="productSliderOne"
							data-slider-options='&#123;"loop": true,"autoplay": true,"spaceBetween":16,"breakpoints":&#123;"0":&#123;"slidesPerView":2.8,"spaceBetween":8&#125;,"430":&#123;"slidesPerView":3.45,"spaceBetween":10&#125;,"576":&#123;"slidesPerView":4.1,"spaceBetween":12&#125;,"768":&#123;"slidesPerView":3&#125;,"992":&#123;"slidesPerView":4&#125;,"1200":&#123;"slidesPerView":6&#125;&#125;&#125;'>
							<div class="swiper-wrapper">
								{#each shown as category (category.id)}
									<div class="swiper-slide">
										<a href={`/shop?category=${category.id}`} class="product-box-items-one">
											<div class="product-box-items-one__icon">
												{#if category.image}
													<img src={category.image} alt={categoryName(category, $locale)} loading="lazy">
												{/if}
											</div>
											<div class="product-box-items-one__content">
												<h6>{categoryName(category, $locale)}</h6>
												<p>{category.products_count ?? 0}  {$translate("Product").toLowerCase()}</p>
											</div>
										</a>
									</div>
								{/each}
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	{/if}

	<section class="home-category-strip" aria-label={$translate('Categories')}>
		<div class="container">
			<div class="home-category-grid">
				{#each shown as category (category.id)}
					<a class="home-category-item" href={`/shop?category=${category.id}`}>
						<span class="home-category-icon">
							{#if category.image}
								<img src={category.image} alt={categoryName(category, $locale)} loading="lazy" />
							{:else}
								<i class="fa-regular fa-grid-2"></i>
							{/if}
						</span>
						<span class="home-category-name">{categoryName(category, $locale)}</span>
					</a>
				{/each}
			</div>
		</div>
	</section>
{/if}

<style>
	.home-category-strip {
		background: #ffffff;
		padding: 24px 0 30px;
	}

	.home-category-grid {
		display: grid;
		grid-template-columns: repeat(9, 104px);
		justify-content: center;
		column-gap: 14px;
		row-gap: 18px;
		align-items: start;
		max-width: var(--home-content-width, 1540px);
		margin: 0 auto;
	}

	.home-category-item {
		display: flex;
		min-width: 0;
		flex-direction: column;
		align-items: center;
		gap: 8px;
		color: #1f2937;
		text-align: center;
		text-decoration: none;
	}

	.home-category-icon {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 96px;
		height: 96px;
		overflow: hidden;
		border-radius: 18px;
		background: #f5f6fa;
		color: #94a3b8;
		font-size: 28px;
		transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
	}

	.home-category-icon img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}

	.home-category-name {
		display: -webkit-box;
		max-width: 118px;
		overflow: hidden;
		color: #1f2937;
		font-size: 14px;
		font-weight: 500;
		line-height: 1.25;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		line-clamp: 2;
	}

	.home-category-item:hover .home-category-icon {
		transform: translateY(-2px);
		background: #eef7fb;
		box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
	}

	.home-category-item:hover .home-category-name {
		color: var(--theme);
	}

	/* Kept only for the disabled legacy slider above. */
	a.product-box-items-one {
		display: block;
		text-decoration: none;
		color: inherit;
	}

	:global(.product-box-items-one__icon) {
		width: 70px;
		height: 70px;
		max-width: 70px;
		border-radius: 50% !important;
		overflow: hidden !important;
		padding: 0 !important;
		margin: 0 auto 15px !important;
		display: flex !important;
		align-items: center !important;
		justify-content: center !important;
		background: #f1f5f9 !important;
		border: 2px solid #e2e8f0 !important;
		box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
		transition: transform 0.3s ease, border-color 0.3s ease;
	}

	.product-box-items-one__icon :global(img) {
		width: 100% !important;
		height: 100% !important;
		object-fit: cover !important;
		border-radius: 50% !important;
		display: block !important;
		transition: transform 0.4s ease !important;
	}

	a.product-box-items-one:hover :global(.product-box-items-one__icon) {
		border-color: #ffffff !important;
	}

	a.product-box-items-one:hover .product-box-items-one__icon :global(img) {
		transform: scale(1.1);
	}

	@media (max-width: 1199.98px) {
		.home-category-grid {
			grid-template-columns: repeat(6, 104px);
			column-gap: 12px;
			row-gap: 18px;
			max-width: 900px;
		}
	}

	@media (max-width: 991.98px) {
		.home-category-grid {
			grid-template-columns: repeat(4, 94px);
			max-width: 680px;
		}

		.home-category-icon {
			width: 86px;
			height: 86px;
		}

		.home-category-icon img {
			width: 100%;
			height: 100%;
		}
	}

	@media (max-width: 575.98px) {
		.home-category-strip {
			padding: 18px 0 22px;
		}

		.home-category-grid {
			grid-template-columns: repeat(3, 82px);
			gap: 16px 10px;
		}

		.home-category-icon {
			width: 74px;
			height: 74px;
			border-radius: 16px;
		}

		.home-category-icon img {
			width: 100%;
			height: 100%;
		}

		.home-category-name {
			max-width: 96px;
			font-size: 13px;
		}
	}

	@media (max-width: 767.98px) {
		.product-section {
			padding: 22px 0 28px;
		}

		.product-section :global(.container) {
			padding-right: 16px;
			padding-left: 16px;
		}

		.productSliderOne :global(.swiper-slide) {
			height: auto;
		}

		a.product-box-items-one {
			height: 118px;
			min-height: 0;
			padding: 13px 8px 11px;
			border-radius: 16px;
		}

		:global(.product-box-items-one__icon) {
			width: 50px !important;
			height: 50px !important;
			aspect-ratio: 1 / 1;
			flex: 0 0 50px !important;
			max-width: 50px !important;
			margin: 0 auto 8px !important;
			border-radius: 50% !important;
			overflow: hidden !important;
		}

		.product-box-items-one__icon :global(img) {
			width: 100% !important;
			height: 100% !important;
			object-fit: cover !important;
		}

		:global(.product-box-items-one__content h6) {
			margin-bottom: 2px;
			font-size: 13px;
			line-height: 1.2;
		}

		:global(.product-box-items-one__content p) {
			font-size: 12px;
			line-height: 1.25;
		}
	}

	@media (max-width: 420px) {
		a.product-box-items-one {
			height: 106px;
			padding: 11px 6px 10px;
		}

		:global(.product-box-items-one__icon) {
			width: 44px !important;
			height: 44px !important;
			flex-basis: 44px !important;
			max-width: 44px !important;
			margin-bottom: 7px !important;
			border-radius: 50% !important;
			overflow: hidden !important;
		}

		.product-box-items-one__icon :global(img) {
			width: 100% !important;
			height: 100% !important;
			object-fit: cover !important;
		}
	}
</style>
