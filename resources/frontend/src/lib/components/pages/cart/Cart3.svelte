<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { basketItems, basketTotal, loadBasket, removeBasketItem, updateBasketItem } from '$lib/services/basket';
	import { isLoggedIn } from '$lib/services/auth';
	import { productImage, productTitle, productUrl } from '$lib/services/products';
	import Button from '$lib/components/ui/Button.svelte';

	let busy = $state<number | null>(null);

	onMount(() => loadBasket());

	async function changeQuantity(id: number, quantity: number) {
		busy = id;
		try {
			await updateBasketItem(id, Math.max(1, quantity));
		} finally {
			busy = null;
		}
	}

	async function remove(id: number) {
		busy = id;
		try {
			await removeBasketItem(id);
		} finally {
			busy = null;
		}
	}

	function selectedColor(item: (typeof $basketItems)[number]) {
		return (
			item.color ??
			item.product?.colors?.find((color) => color.id === item.color_id) ??
			null
		);
	}

	function selectedSize(item: (typeof $basketItems)[number]) {
		return (
			item.size ??
			item.product?.sizes?.find((size) => size.id === item.size_id) ??
			null
		);
	}
</script>

<!-- Cart Section -->
<div class="cart-wrapper section-padding fix bg-white">
	<div class="container">
		{#if !$isLoggedIn}
			<div class="text-center py-5">
				<p class="text-muted mb-3">{$translate('Please sign in to see your cart.')}</p>
				<Button href="/login">{$translate('Login')}</Button>
			</div>
		{:else if $basketItems.length === 0}
			<div class="text-center py-5">
				<p class="text-muted mb-3">{$translate('Your cart is empty.')}</p>
				<Button href="/shop">{$translate('Continue shopping')}</Button>
			</div>
		{:else}
			<div class="row g-4 align-items-start">
				<!-- Sol Tərəf: Məhsullar Cədvəli -->
				<div class="col-lg-8 col-12">
					<div class="cart-table-card cart-list-card">
						<div class="cart-list-head">
							<span>{$translate('Product')}</span>
							<span>{$translate('Price')}</span>
							<span>{$translate('Quantity')}</span>
							<span>{$translate('Total')}</span>
							<span></span>
						</div>

						<div class="cart-list">
							{#each $basketItems as item (item.id)}
								{@const color = selectedColor(item)}
								{@const size = selectedSize(item)}
								<div class="cart-item-row">
									<div class="cart-product-cell">
										<a href={item.product ? productUrl(item.product) : '/shop'} class="thumb-link">
											<img
												src={item.product ? productImage(item.product) : ''}
												alt={item.product ? productTitle(item.product) : 'product'}
											/>
										</a>
										<div class="cart-product-info">
											<a
												class="product-name"
												href={item.product ? productUrl(item.product) : '/shop'}
											>
												{item.product ? productTitle(item.product) : 'Product'}
											</a>
											<div class="variant-chips">
												{#if color}
													<span class="variant-chip">
														<span class="variant-dot" style={`background-color: ${color.hex ?? '#d1d5db'}`}></span>
														{color.name ?? 'Color'}
													</span>
												{/if}
												{#if size}
													<span class="variant-chip">
														<i class="fa-light fa-ruler-combined"></i>
														{size.name ?? 'Size'}
													</span>
												{/if}
											</div>
										</div>
									</div>

									<div class="cart-price">${Number(item.retail_unit_price ?? 0).toFixed(2)}</div>

									<div class="cart-qty">
										<button
											type="button"
											disabled={busy === item.id}
											onclick={() => changeQuantity(item.id, item.quantity - 1)}
											aria-label="Decrease quantity"
										>
											<i class="fa fa-minus" aria-hidden="true"></i>
										</button>
										<input
											class="form-control"
											type="number"
											min="1"
											value={item.quantity}
											readonly
											aria-label="Quantity"
										/>
										<button
											type="button"
											disabled={busy === item.id}
											onclick={() => changeQuantity(item.id, item.quantity + 1)}
											aria-label="Increase quantity"
										>
											<i class="fa fa-plus" aria-hidden="true"></i>
										</button>
									</div>

									<div class="cart-total">${Number(item.retail_total ?? 0).toFixed(2)}</div>

									<button
										type="button"
										class="cart-remove"
										disabled={busy === item.id}
										aria-label="Remove item"
										title="Remove item"
										onclick={() => remove(item.id)}
									>
										<i class="fa-regular fa-trash-can"></i>
									</button>
								</div>
							{/each}
						</div>
					</div>
				</div>

				<!-- Sağ Tərəf: Cart Totals Paneli -->
				<div class="col-lg-4 col-12">
					<div class="cart-totals-card">
						<h3 class="totals-title">{$translate('Cart Totals')}</h3>
						<div class="totals-body">
							<div class="totals-row">
								<span class="totals-label">{$translate('Subtotal')}</span>
								<span class="totals-val">${Number($basketTotal).toFixed(2)}</span>
							</div>
							<div class="totals-divider"></div>

							<div class="totals-row total-highlight">
								<span class="totals-label">{$translate('Total')}</span>
								<span class="totals-val total-price">${Number($basketTotal).toFixed(2)}</span>
							</div>
						</div>
						<div class="totals-action">
							<a href="/checkout" class="btn-checkout">
								Proceed To Checkout
							</a>
						</div>
					</div>
				</div>
			</div>
		{/if}
	</div>
</div>

<style>
	.cart-table-card {
		background: #ffffff;
		border: 1px solid #edf0f5;
		border-radius: 14px;
		padding: 14px;
		box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
		overflow: hidden;
	}

	.cart-list-head,
	.cart-item-row {
		display: grid;
		grid-template-columns: minmax(180px, 1fr) 76px 104px 78px 30px;
		gap: 10px;
		align-items: center;
	}

	.cart-list-head {
		padding: 8px 10px 12px;
		border-bottom: 1px solid #e5eaf2;
		color: #64748b;
		font-size: 11px;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.03em;
		background: #f8fafc;
		border-radius: 10px;
	}

	.cart-list {
		display: grid;
		gap: 0;
	}

	.cart-item-row {
		padding: 16px 10px;
		border-bottom: 1px solid #edf0f5;
	}

	.cart-item-row:last-child {
		border-bottom: 0;
	}

	.cart-product-cell {
		display: flex;
		align-items: center;
		gap: 12px;
		min-width: 0;
	}

	.thumb-link {
		flex-shrink: 0;
		display: inline-block;
	}

	.thumb-link img {
		width: 58px;
		height: 58px;
		object-fit: cover;
		border-radius: 10px;
		background: #f8f9fa;
		border: 1px solid #edf0f5;
	}

	.cart-product-info {
		min-width: 0;
	}

	.product-name {
		font-weight: 600;
		color: #1e2532;
		text-decoration: none;
		font-size: 15px;
		line-height: 1.35;
		transition: color 0.15s;
		display: block;
		max-width: 160px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.product-name:hover {
		color: #ef3e2e;
	}

	.variant-chips {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
		margin-top: 8px;
	}

	.variant-chip {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		min-height: 24px;
		padding: 4px 8px;
		border: 1px solid #e5eaf2;
		border-radius: 999px;
		background: #f8fafc;
		color: #475569;
		font-size: 12px;
		font-weight: 700;
		line-height: 1;
	}

	.variant-dot {
		width: 10px;
		height: 10px;
		border-radius: 999px;
		border: 1px solid rgba(15, 23, 42, 0.16);
	}

	.cart-price,
	.cart-total {
		color: #1e2532;
		font-size: 13px;
		font-weight: 700;
		white-space: nowrap;
	}

	.cart-total {
		text-align: right;
	}

	.cart-qty {
		display: inline-flex;
		align-items: center;
		border: 1px solid #e2e7f0;
		border-radius: 10px;
		background: #ffffff;
		overflow: hidden;
		height: 32px;
		justify-self: start;
	}

	.cart-qty button {
		background: #f8fafc;
		border: none;
		width: 28px;
		height: 30px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		color: #4b5563;
		font-size: 11px;
		transition: background-color 0.15s, color 0.15s;
	}

	.cart-qty button:hover:not(:disabled) {
		background: #eef2f6;
		color: #ef3e2e;
	}

	.cart-qty button:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.cart-qty input {
		width: 34px;
		height: 30px;
		min-height: 30px;
		text-align: center;
		border: none;
		border-left: 1px solid #e2e7f0;
		border-right: 1px solid #e2e7f0;
		background: #ffffff;
		font-weight: 600;
		font-size: 13px;
		line-height: 30px;
		color: #1e2532;
		padding: 0;
		border-radius: 0;
		box-shadow: none;
		appearance: textfield;
		-moz-appearance: textfield;
	}

	.cart-qty input::-webkit-outer-spin-button,
	.cart-qty input::-webkit-inner-spin-button {
		margin: 0;
		appearance: none;
	}

	.cart-remove {
		background: none;
		border: none;
		cursor: pointer;
		color: #ef3e2e;
		font-size: 16px;
		width: 32px;
		height: 32px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		border-radius: 8px;
		transition: background-color 0.15s, color 0.15s, transform 0.1s;
	}

	.cart-remove:hover {
		background-color: #feebe9;
		color: #db2d1d;
	}

	.cart-remove:active {
		transform: scale(0.95);
	}

	/* Sağ Tərəf: Cart Totals Kartı */
	.cart-totals-card {
		background: #ffffff;
		border: 1px solid #edf0f5;
		border-radius: 14px;
		padding: 26px 24px;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
		position: sticky;
		top: 100px;
	}

	.totals-title {
		font-size: 19px;
		font-weight: 700;
		color: #1e2532;
		margin: 0 0 18px;
		padding-bottom: 12px;
		border-bottom: 1px solid #edf0f5;
	}

	.totals-body {
		margin-bottom: 22px;
	}

	.totals-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 10px 0;
		font-size: 14.5px;
	}

	.totals-label {
		color: #64748b;
	}

	.totals-val {
		color: #1e2532;
		font-weight: 600;
	}

	.totals-divider {
		height: 1px;
		background: #edf0f5;
		margin: 6px 0;
	}

	.total-highlight .totals-label {
		font-size: 16px;
		font-weight: 700;
		color: #1e2532;
	}

	.total-highlight .total-price {
		font-size: 20px;
		font-weight: 700;
		color: #1e2532;
	}

	.totals-action {
		margin-top: 8px;
	}

	.btn-checkout {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 100%;
		padding: 13px 24px;
		background: #ef3e2e;
		color: #ffffff;
		font-size: 15px;
		font-weight: 600;
		border-radius: 30px;
		text-decoration: none;
		transition: background-color 0.2s, transform 0.2s, box-shadow 0.2s;
		box-shadow: 0 4px 14px rgba(239, 62, 46, 0.25);
	}

	.btn-whatsapp {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		width: 100%;
		margin-top: 12px;
		padding: 14px 20px;
		border: 0;
		border-radius: 12px;
		background: #25d366;
		color: #fff;
		font-weight: 700;
		font-size: 15px;
		cursor: pointer;
		transition: background 0.2s ease;
	}

	.btn-whatsapp:hover:not(:disabled) {
		background: #1ebe5b;
	}

	.btn-whatsapp:disabled {
		opacity: 0.7;
		cursor: default;
	}

	.wa-error {
		margin-top: 8px;
		font-size: 13px;
		color: #dc2626;
	}

	.btn-checkout:hover {
		background: #db2d1d;
		color: #ffffff;
		transform: translateY(-1px);
		box-shadow: 0 6px 18px rgba(239, 62, 46, 0.35);
	}

	@media (min-width: 1200px) {
		.cart-list-head,
		.cart-item-row {
			grid-template-columns: minmax(220px, 1fr) 88px 112px 90px 32px;
			gap: 14px;
		}

		.product-name {
			max-width: 210px;
		}

		.thumb-link img {
			width: 64px;
			height: 64px;
		}
	}

	@media (max-width: 991px) {
		.cart-totals-card {
			position: static;
			margin-top: 10px;
		}

		.cart-list-head {
			display: none;
		}

		.cart-item-row {
			grid-template-columns: 1fr auto;
			row-gap: 12px;
			column-gap: 12px;
			align-items: center;
			padding: 16px 4px;
		}

		.cart-product-cell {
			grid-column: 1;
			grid-row: 1;
		}

		.product-name {
			max-width: 100%;
		}

		.cart-remove {
			grid-column: 2;
			grid-row: 1;
			justify-self: end;
			align-self: start;
			margin-top: 2px;
		}

		.cart-price {
			grid-column: 1;
			grid-row: 2;
		}

		.cart-price::before {
			content: 'Price: ';
			color: #64748b;
			font-weight: 600;
		}

		.cart-qty {
			grid-column: 2;
			grid-row: 2;
			justify-self: end;
		}

		.cart-total {
			grid-column: 1 / -1;
			grid-row: 3;
			text-align: left;
		}

		.cart-total::before {
			content: 'Total: ';
			color: #64748b;
			font-weight: 600;
		}
	}
</style>
