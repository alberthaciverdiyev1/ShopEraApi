<script lang="ts">
	import { onMount } from 'svelte';
	import { page } from '$app/state';
	import { fetchVendor, vendorName, vendorDescription, type ApiVendor } from '$lib/services/vendors';
	import { fetchShopProducts, type ShopMeta } from '$lib/services/shop';
	import { type ApiProduct } from '$lib/services/products';
	import ShopProductCard from '$lib/components/cards/ShopProductCard.svelte';

	const slug = $derived((page.params as Record<string, string>).slug ?? '');

	let vendor = $state<ApiVendor | null>(null);
	let items = $state<ApiProduct[]>([]);
	let meta = $state<ShopMeta>({ current_page: 1, last_page: 1, per_page: 24, total: 0 });
	let loading = $state(true);
	let loadingMore = $state(false);
	let notFound = $state(false);

	async function load(targetPage = 1, append = false) {
		append ? (loadingMore = true) : (loading = true);
		try {
			const result = await fetchShopProducts({
				marketplace: true,
				vendor_id: vendor?.id,
				page: targetPage,
				per_page: 24
			});
			items = append ? [...items, ...result.items] : result.items;
			meta = result.meta;
		} finally {
			loading = false;
			loadingMore = false;
		}
	}

	onMount(async () => {
		try {
			vendor = await fetchVendor(slug);
		} catch {
			notFound = true;
			loading = false;
			return;
		}
		await load();
	});
</script>

<svelte:head><title>{vendor ? vendorName(vendor) : 'Mağaza'}</title></svelte:head>

<section class="section-padding fix">
	<div class="container">
		{#if notFound}
			<p class="text-muted">Mağaza tapılmadı.</p>
		{:else if !vendor}
			<p class="text-muted">Yüklənir…</p>
		{:else}
			<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
				{#if vendor.logo_url}
					<img src={vendor.logo_url} alt={vendorName(vendor)} class="rounded" style="width:84px;height:84px;object-fit:cover;">
				{:else}
					<span class="rounded d-flex align-items-center justify-content-center"
					      style="width:84px;height:84px;background:color-mix(in srgb, var(--theme) 12%, #fff);color:var(--theme);font-size:34px;font-weight:800;">
						{vendorName(vendor).charAt(0).toUpperCase()}
					</span>
				{/if}
				<div>
					<h2 class="mb-1">
						{vendorName(vendor)}
						{#if (vendor.rating_count ?? 0) > 0}
							<span class="fs-6 text-warning ms-2">★ {Number(vendor.rating_avg ?? 0).toFixed(1)}</span>
							<span class="fs-6 text-muted">({vendor.rating_count})</span>
						{/if}
					</h2>
					{#if vendorDescription(vendor)}<p class="text-muted mb-1">{vendorDescription(vendor)}</p>{/if}
					<p class="mb-0 small text-muted">
						{vendor.listings_count ?? 0} elan
						{#if vendor.address} · {vendor.address}{/if}
						{#if vendor.phone} · <a href={`tel:${vendor.phone}`}>{vendor.phone}</a>{/if}
					</p>
				</div>
			</div>

			{#if loading}
				<p class="text-muted">Yüklənir…</p>
			{:else if items.length === 0}
				<p class="text-muted">Bu mağazada hələ elan yoxdur.</p>
			{:else}
				<div class="row g-4">
					{#each items as product (product.id)}
						<div class="col-6 col-md-4 col-lg-3">
							<ShopProductCard {product} />
						</div>
					{/each}
				</div>
				{#if meta.current_page < meta.last_page}
					<div class="text-center mt-4">
						<button class="theme-btn" type="button" disabled={loadingMore} onclick={() => load(meta.current_page + 1, true)}>
							{loadingMore ? 'Yüklənir…' : 'Daha çox'}
						</button>
					</div>
				{/if}
			{/if}
		{/if}
	</div>
</section>
