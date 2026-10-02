<script lang="ts">
	import { hasDiscount, productImage, productTitle, productUrl, type ApiProduct } from '$lib/services/products';
	import { basketProductIds } from '$lib/services/basket';
	import { favoriteIds } from '$lib/services/favorites';
	import { toggleFavoriteProduct } from '$lib/services/favorite-actions';
	import { addProductToCart } from '$lib/services/basket-actions';
	import { translate } from '$lib/i18n';

	let { product }: { product: ApiProduct } = $props();
</script>

<div class="best-seller-one">
    <a class="card-link" href={productUrl(product)} aria-label={productTitle(product)}></a>
    <div class="best-seller-one__thumb">
        <a href={productUrl(product)}>
            <img src={productImage(product)} alt={productTitle(product)} loading="lazy">
        </a>
    </div>
    <div class="best-seller-one__content">
        <h4 class="best-seller-one__content-title">
            <a href={productUrl(product)}>{productTitle(product)}</a>
        </h4>
        <div class="best-seller-one__star-wrap">
            <div class="star">
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
            </div>
            <span>{product.rate_count ?? 0} {$translate('Reviews')}</span>
        </div>
        <h4 class="best-seller-one__content-price">
            <span class="offer-price">${Number(product.discount || product.price).toFixed(2)}</span>
            {#if hasDiscount(product)}
                <span class="original-price">${Number(product.price).toFixed(2)}</span>
            {/if}
        </h4>
        <div class="best-seller-one__icons">
            <button type="button" class="fav-btn" class:active={$favoriteIds.has(product.id)}
                                                            aria-label={$translate('Add to wishlist')}
                                                            onclick={() => toggleFavoriteProduct(product.id)}>
                                                    <i class="fa-light fa-heart"></i>
                                                </button>
            <button type="button" class="add-to-cart-btn" class:active={$basketProductIds.has(product.id)} aria-label={$translate('Add to cart')}
                                                            onclick={() => addProductToCart(product.id)}>
                                                    <i class="fa-solid fa-cart-shopping"></i>
                                                </button>
        </div>
    </div>
</div>

<style>
	.best-seller-one {
		display: block;
		position: relative;
		height: 100%;
		min-height: 0;
		padding: 0;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 14px;
		background: #ffffff;
		overflow: hidden;
	}

	.best-seller-one__thumb {
		width: 100%;
		height: auto;
		margin: 0 0 10px;
	}

	.best-seller-one__thumb a {
		display: block;
		height: 260px;
		border-radius: 12px;
		overflow: hidden;
	}

	.best-seller-one__thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}

	.best-seller-one__content {
		padding: 0 12px 12px;
	}

	.best-seller-one__content-title {
		margin-bottom: 5px;
		font-size: 14px;
		line-height: 1.25;
	}

	.best-seller-one__content-title a {
		display: -webkit-box;
		overflow: hidden;
		color: inherit;
		text-decoration: none;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		line-clamp: 2;
	}

	.best-seller-one__star-wrap {
		display: flex;
		gap: 4px;
		align-items: center;
		margin-bottom: 6px;
	}

	.best-seller-one__star-wrap .star {
		font-size: 11px;
		line-height: 1;
		white-space: nowrap;
	}

	.best-seller-one__star-wrap span {
		font-size: 11px;
		white-space: nowrap;
	}

	.best-seller-one__content-price {
		font-size: 13px;
		line-height: 1.25;
	}

	.best-seller-one__icons {
		position: absolute;
		top: 16px;
		right: 16px;
		z-index: 5;
		display: flex;
		gap: 6px;
	}

	.best-seller-one__icons button {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 32px;
		height: 32px;
		padding: 0;
		border: 1px solid rgba(15, 23, 42, 0.1);
		border-radius: 50%;
		background: #fff;
		color: #111827;
		font-size: 13px;
		line-height: 1;
		box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1);
	}

	.best-seller-one__icons button.active,
	.best-seller-one__icons button:hover {
		background: var(--theme);
		border-color: var(--theme);
		color: #fff;
	}
	@media (max-width: 767.98px) {
		.best-seller-one__thumb a {
			height: 180px;
		}
	}
</style>
