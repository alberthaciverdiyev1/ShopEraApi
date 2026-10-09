<script lang="ts">
	const CART_ENABLED = false;
    import {hasDiscount, productImage, productTitle, productUrl, type ApiProduct} from '$lib/services/products';
    import {basketProductIds} from '$lib/services/basket';
    import {favoriteIds} from '$lib/services/favorites';
    import {toggleFavoriteProduct} from '$lib/services/favorite-actions';
    import {addProductToCart} from '$lib/services/basket-actions';
    import Badge from '$lib/components/ui/Badge.svelte';
    import {translate} from '$lib/i18n';
    import {features} from '$lib/services/features';


    let {product}: { product: ApiProduct } = $props();

    const discountPercent = $derived(
        hasDiscount(product)
            ? Math.round(((Number(product.price) - Number(product.discount)) / Number(product.price)) * 100)
            : 0
    );
    const listingMeta = $derived([product.city || 'Bakı', formatListingDate(product.created_at)].filter(Boolean).join(', '));

    // Paid placement badge: Premium > VIP > İrəli çək.
    const placement = $derived(
        product.is_premium
            ? { label: 'Premium', cls: 'is-premium' }
            : product.is_vip
                ? { label: 'VIP', cls: 'is-vip' }
                : product.is_promoted
                    ? { label: 'İrəli çəkildi', cls: 'is-promoted' }
                    : null
    );

    function formatListingDate(value?: string | null): string {
        if (!value) return '';

        const date = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return '';

        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const listingDay = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const diffDays = Math.round((today.getTime() - listingDay.getTime()) / 86400000);
        const time = date.toLocaleTimeString('az-AZ', { hour: '2-digit', minute: '2-digit' });

        if (diffDays === 0) return `Bu gün, ${time}`;
        if (diffDays === 1) return `Dünən, ${time}`;

        return `${date.toLocaleDateString('az-AZ', { day: '2-digit', month: '2-digit', year: 'numeric' })}, ${time}`;
    }
</script>

<div class="best-seller-product-items-two item-border">
    <a class="card-link" href={productUrl(product)} aria-label={productTitle(product)}></a>
    <div class="icon-box2">
        {#if $features.favorites}
            <button type="button" class="fav-btn" class:active={$favoriteIds.has(product.id)}
                    aria-label={$translate('Add to wishlist')}
                    onclick={() => toggleFavoriteProduct(product.id)}>
                <i class="fa-regular fa-heart"></i>
            </button>
        {/if}
        {#if CART_ENABLED}
        <button type="button" class="add-to-cart-btn" class:active={$basketProductIds.has(product.id)}
                aria-label={$translate('Add to cart')}
                onclick={() => addProductToCart(product.id)}>
            <i class="fa-solid fa-cart-shopping"></i>
        </button>
        {/if}
    </div>
    {#if discountPercent > 0}
        <Badge text={`-${discountPercent}%`} variant="off"/>
    {/if}
    <div class="best-seller-product-items-two__thumb">
        <img src={productImage(product)} alt={productTitle(product)} loading="lazy">
        {#if product.rate}
            <div class="card-rating">
                <i class="fa-solid fa-star"></i>
                <b>{Number(product.rate).toFixed(1)}</b>
                <span>({product.rate_count ?? 0})</span>
            </div>
        {/if}
        {#if placement}
            <span class="card-placement {placement.cls}">{placement.label}</span>
        {/if}
    </div>
    <div class="best-seller-product-items-two__content">
        <div class="best-seller-product-items-two__details">
<!--            <p class="best-seller-product-items-two__details&#45;&#45;subtitle">{product.category?.name ?? ''}</p>-->
            <div class="best-seller-product-items-two__details--price"> <span
                class="offer-price">${Number(product.discount || product.price).toFixed(2)}</span>
                {#if hasDiscount(product)}<span
                    class="original-price">${Number(product.price).toFixed(2)}</span>{/if}
            </div>
            <h6 class="best-seller-product-items-two__details--title">
                <a href={productUrl(product)}>{productTitle(product)}</a>
            </h6>
            {#if listingMeta}
                <a href={productUrl(product)} class="listing-card-meta">{listingMeta}</a>
            {/if}
        </div>
    </div>
</div>

<style>
    .best-seller-product-items-two__thumb {
        position: relative;
        height: 230px;
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

    /* Paid placement pill, bottom-right of the photo. */
    .card-placement {
        position: absolute;
        right: 10px;
        bottom: 10px;
        z-index: 6;
        padding: 3px 10px;
        border-radius: 999px;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .card-placement.is-promoted {
        background: #0ea5e9;
    }

    .card-placement.is-vip {
        background: #f59e0b;
    }

    .card-placement.is-premium {
        background: #7c3aed;
    }

    .listing-card-meta {
        display: block;
        margin-top: 5px;
        overflow: hidden;
        color: #8b95a5 !important;
        font-size: 13px;
        font-weight: 400;
        line-height: 1.25;
        text-decoration: none;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .listing-card-meta:hover {
        color: #6b7280 !important;
    }

    @media (max-width: 767.98px) {
        .listing-card-meta {
            font-size: 12px;
        }
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
