<script lang="ts">
	import { hasDiscount, productImage, productTitle, productUrl, type ApiProduct } from '$lib/services/products';
	import { basketProductIds } from '$lib/services/basket';
	import { favoriteIds } from '$lib/services/favorites';
	import { toggleFavoriteProduct } from '$lib/services/favorite-actions';
	import { addProductToCart } from '$lib/services/basket-actions';
	import Badge from '$lib/components/ui/Badge.svelte';
	import { translate } from '$lib/i18n';

	let { product }: { product: ApiProduct } = $props();

	const discountPercent = $derived(
		hasDiscount(product)
			? Math.round(((Number(product.price) - Number(product.discount)) / Number(product.price)) * 100)
			: 0
	);
</script>

<div class="best-seller-product-items-two item-border">
    <a class="card-link" href={productUrl(product)} aria-label={productTitle(product)}></a>
                 <div class="icon-box2">
                    <button type="button" class="fav-btn" class:active={$favoriteIds.has(product.id)}
                                                            aria-label={$translate('Add to wishlist')}
                                                            onclick={() => toggleFavoriteProduct(product.id)}>
                                                    <i class="fa-regular fa-heart"></i>
                                                </button>
                    <button type="button" class="add-to-cart-btn" class:active={$basketProductIds.has(product.id)} aria-label={$translate('Add to cart')}
                                                            onclick={() => addProductToCart(product.id)}>
                                                    <i class="fa-solid fa-cart-shopping"></i>
                                                </button>
                </div>
                {#if discountPercent > 0}<Badge text={`-${discountPercent}%`} variant="off" />{/if}
                <div class="best-seller-product-items-two__thumb">
                    <img src={productImage(product)} alt={productTitle(product)} loading="lazy">
                    {#if product.rate}
                        <div class="card-rating">
                            <i class="fa-solid fa-star"></i>
                            <b>{Number(product.rate).toFixed(1)}</b>
                            <span>({product.rate_count ?? 0})</span>
                        </div>
                    {/if}
                </div>
                <div class="best-seller-product-items-two__content">
                    <div class="best-seller-product-items-two__details">
                        <p class="best-seller-product-items-two__details--subtitle">{product.category?.name ?? ''}</p>
                        <h6 class="best-seller-product-items-two__details--title">
                            <a href={productUrl(product)}>{productTitle(product)}</a>
                        </h6>
                        <div class="best-seller-product-items-two__details--price"> <span
                                class="offer-price">${Number(product.discount || product.price).toFixed(2)}</span>
                            {#if hasDiscount(product)}<span
                                class="original-price">${Number(product.price).toFixed(2)}</span>{/if}
                        </div>
                    </div>
                </div>
            </div>

<style>
	.best-seller-product-items-two__thumb {
		position: relative;
		height: 260px;
		overflow: hidden;
		border-radius: 12px 12px 0 0;
	}

	.best-seller-product-items-two__thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}

	@media (max-width: 767.98px) {
		.best-seller-product-items-two__thumb {
			height: 180px;
		}
	}

	/* Rating badge sits ON the photo, right above the category line. */
	.card-rating {
		position: absolute;
		left: 10px;
		bottom: 10px;
		z-index: 6;
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 4px 10px;
		border-radius: 999px;
		background: rgba(15, 23, 42, 0.72);
		backdrop-filter: blur(4px);
		color: #fff;
		font-size: 12px;
		font-weight: 700;
		line-height: 1;
	}

	.card-rating i {
		color: #fbbf24;
		font-size: 11px;
	}

	.card-rating span {
		color: #cbd5e1;
		font-weight: 600;
	}

	.best-seller-product-items-two :global(.best-seller-product-items-two__badge1) {
		top: 12px !important;
		left: 12px !important;
		min-width: 0;
		height: 36px;
		padding: 0 12px;
		border-radius: 999px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		font-size: 12px;
		font-weight: 800;
		line-height: 1;
		letter-spacing: 0;
		white-space: nowrap;
		z-index: 5;
		box-shadow: 0 10px 24px rgba(16, 163, 74, 0.22);
	}

	@media (max-width: 767.98px) {
		.best-seller-product-items-two :global(.best-seller-product-items-two__badge1) {
			top: 5px !important;
			left: 5px !important;
			height: 24px;
			padding: 0 8px;
			font-size: 10px;
		}
	}
</style>
