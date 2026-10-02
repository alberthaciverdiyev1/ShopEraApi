<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { favoriteProducts, favoritesLoading, loadFavorites } from '$lib/services/favorites';
	import { isLoggedIn } from '$lib/services/auth';
	import ShopProductCard from '$lib/components/cards/ShopProductCard.svelte';
	import Button from '$lib/components/ui/Button.svelte';

	onMount(() => {
		loadFavorites();
	});
</script>

<!-- Wishlist Section -->
<div class="wishlist-wrapper section-padding fix bg-white">
	<div class="container">
		{#if !$isLoggedIn}
			<div class="wishlist-empty-state text-center py-5">
				<div class="empty-icon mb-3">
					<i class="fa-light fa-heart"></i>
				</div>
				<h4 class="fw-bold mb-2">{$translate('İstək siyahınızı görmək üçün daxil olun')}</h4>
				<p class="text-muted mb-4 mx-auto" style="max-width: 440px;">
					{$translate('Bəyəndiyiniz məhsulları yadda saxlamaq və istədiyiniz vaxt yenidən nəzərdən keçirmək üçün hesabınıza giriş edin.')}
				</p>
				<Button href="/login">Daxil ol</Button>
			</div>
		{:else if $favoritesLoading && $favoriteProducts.length === 0}
			<div class="text-center py-5">
				<div class="spinner-border text-theme" role="status">
					<span class="visually-hidden">{$translate('Yüklənir...')}</span>
				</div>
				<p class="text-muted mt-3 mb-0">{$translate('İstək siyahınız yüklənir...')}</p>
			</div>
		{:else if $favoriteProducts.length === 0}
			<div class="wishlist-empty-state text-center py-5">
				<div class="empty-icon mb-3">
					<i class="fa-light fa-heart-crack"></i>
				</div>
				<h4 class="fw-bold mb-2">{$translate('İstək siyahınız boşdur')}</h4>
				<p class="text-muted mb-4 mx-auto" style="max-width: 440px;">
					{$translate('Hələ heç bir məhsulu istək siyahınıza əlavə etməmisiniz. Mağazadakı məhsullara baxaraq bəyəndiklərinizi əlavə edə bilərsiniz.')}
				</p>
				<Button href="/shop">{$translate('Məhsullara bax')}</Button>
			</div>
		{:else}
			<div class="wishlist-header d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom flex-wrap gap-3">
				<div>
					<h4 class="fw-bold mb-1">{$translate('Mənim İstək Siyahım')}</h4>
					<p class="text-muted small mb-0">Toplam <strong>{$favoriteProducts.length}</strong> {$translate('məhsul')}</p>
				</div>
				<a href="/shop" class="theme-btn btn-sm btn-outline">
					<i class="fa-regular fa-bag-shopping me-1"></i> {$translate('Alış-verişə davam et')}
				</a>
			</div>

			<div class="row g-2 g-sm-3 g-md-4">
				{#each $favoriteProducts as product (product.id)}
					<div class="col-6 col-sm-6 col-md-4 col-lg-3">
						<ShopProductCard {product} />
					</div>
				{/each}
			</div>
		{/if}
	</div>
</div>

<style>
	.empty-icon {
		width: 80px;
		height: 80px;
		background: rgba(239, 35, 60, 0.08);
		color: var(--theme, #ef233c);
		border-radius: 50%;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		font-size: 36px;
	}

	.text-theme {
		color: var(--theme, #ef233c) !important;
	}

	.btn-outline {
		background: transparent !important;
		color: var(--theme, #ef233c) !important;
		border: 1px solid var(--theme, #ef233c) !important;
		transition: all 0.2s ease-in-out;
	}

	.btn-outline:hover {
		background: var(--theme, #ef233c) !important;
		color: #fff !important;
	}

	@media (max-width: 575.98px) {
		:global(.wishlist-wrapper .col-6) {
			padding-right: 4px;
			padding-left: 4px;
		}

		:global(.wishlist-wrapper .best-seller-product-items-two) {
			border-radius: 11px !important;
		}
	}
</style>
