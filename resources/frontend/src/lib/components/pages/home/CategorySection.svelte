<script lang="ts">
	import { onMount } from 'svelte';
	import { slider } from '$lib/theme/slider';
	import { categories, categoryName, loadCategories } from '$lib/services/categories';

	onMount(() => loadCategories());
</script>

{#if $categories.length > 0}
	<!-- Product Section (Popular Categories) -->
	<section class="product-section section-padding fix">
		<div class="product-contianer-wrapper style1">
			<div class="container">
				<div class="row">
					{#key $categories.length}
					<div class="swiper gt-slider productSliderOne" id="productSliderOne" use:slider
						data-slider-options='&#123;"loop": true,"autoplay": true,"spaceBetween":16,"breakpoints":&#123;"0":&#123;"slidesPerView":2.8,"spaceBetween":8&#125;,"430":&#123;"slidesPerView":3.45,"spaceBetween":10&#125;,"576":&#123;"slidesPerView":4.1,"spaceBetween":12&#125;,"768":&#123;"slidesPerView":3&#125;,"992":&#123;"slidesPerView":4&#125;,"1200":&#123;"slidesPerView":6&#125;&#125;&#125;'>
						<div class="swiper-wrapper">
							{#each $categories as category (category.id)}
								<div class="swiper-slide">
									<a href={`/shop?category=${category.id}`} class="product-box-items-one">
										<div class="product-box-items-one__icon">
											{#if category.image}
												<img src={category.image} alt={categoryName(category)} loading="lazy">
											{/if}
										</div>
										<div class="product-box-items-one__content">
											<h6>{categoryName(category)}</h6>
											<p>{category.products_count ?? 0} items</p>
										</div>
									</a>
								</div>
							{/each}
						</div>
					</div>
					{/key}
				</div>
			</div>
		</div>
	</section>
{/if}

<style>
	/* The card is a link now; keep it looking exactly like the template's div. */
	a.product-box-items-one {
		display: block;
		text-decoration: none;
		color: inherit;
	}

	/* Category circle container on desktop */
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

	/* Category photo completely fills the circular icon */
	.product-box-items-one__icon :global(img) {
		width: 100% !important;
		height: 100% !important;
		object-fit: cover !important;
		border-radius: 50% !important;
		display: block !important;
		transition: transform 0.4s ease !important;
	}

	/* Card hover zoom on photo */
	a.product-box-items-one:hover :global(.product-box-items-one__icon) {
		border-color: #ffffff !important;
	}

	a.product-box-items-one:hover .product-box-items-one__icon :global(img) {
		transform: scale(1.1);
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
