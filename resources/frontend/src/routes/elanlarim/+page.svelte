<script lang="ts">
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { isLoggedIn } from '$lib/services/auth';
	import { fetchMyListings, deleteListing, listingTitle, listingImage, fetchPromotionPackages, promoteListing, packageName, type ApiListing, type PromotionPackage } from '$lib/services/listings';

	let listings = $state<ApiListing[]>([]);
	let loading = $state(true);
	let error = $state<string | null>(null);

	async function load() {
		loading = true;
		try {
			listings = await fetchMyListings();
		} catch (e) {
			error = e instanceof Error ? e.message : 'Yüklənə bilmədi.';
		} finally {
			loading = false;
		}
	}

	async function remove(id: number) {
		if (!confirm('Elan silinsin?')) return;
		await deleteListing(id);
		await load();
	}

	// Promotion (paid placement).
	let promoteFor = $state<ApiListing | null>(null);
	let packages = $state<PromotionPackage[]>([]);
	let selectedPackage = $state<number | null>(null);
	let promoting = $state(false);
	let promoteMsg = $state<string | null>(null);

	async function openPromote(listing: ApiListing) {
		promoteFor = listing;
		promoteMsg = null;
		selectedPackage = null;
		packages = await fetchPromotionPackages();
	}

	async function submitPromote() {
		if (!promoteFor || !selectedPackage) return;
		promoting = true;
		try {
			await promoteListing(promoteFor.id, selectedPackage);
			promoteMsg = 'Sifariş yaradıldı. Ödəniş təsdiqləndikdən sonra irəli çəkiləcək.';
		} catch (e) {
			promoteMsg = e instanceof Error ? e.message : 'Alınmadı.';
		} finally {
			promoting = false;
		}
	}

	onMount(() => {
		if (!$isLoggedIn) {
			goto('/login');
			return;
		}
		load();
	});
</script>

<section class="section-padding fix">
	<div class="container">
		<div class="d-flex align-items-center justify-content-between mb-4">
			<h2 class="mb-0">Elanlarım</h2>
			<a href="/elan/ver" class="theme-btn">Yeni elan</a>
		</div>

		{#if error}<div class="alert alert-danger">{error}</div>{/if}

		{#if loading}
			<p class="text-muted">Yüklənir…</p>
		{:else if listings.length === 0}
			<p class="text-muted">Hələ elanınız yoxdur.</p>
		{:else}
			<div class="row g-4">
				{#each listings as listing (listing.id)}
					<div class="col-sm-6 col-lg-3">
						<div class="card h-100 border-0 shadow-sm">
							<img src={listingImage(listing)} alt={listingTitle(listing)} class="card-img-top"
							     style="height:180px;object-fit:cover;">
							<div class="card-body d-flex flex-column">
								<p class="fw-semibold mb-1">{listingTitle(listing)}</p>
								<p class="text-primary fw-bold mb-2">{Number(listing.price ?? 0).toFixed(2)} ₼</p>
								<div class="d-flex gap-2 mt-auto">
									<button class="btn btn-outline-primary btn-sm flex-grow-1" type="button"
									        onclick={() => openPromote(listing)}>İrəli çək</button>
									<button class="btn btn-outline-danger btn-sm" type="button"
									        onclick={() => remove(listing.id)}>Sil</button>
								</div>
							</div>
						</div>
					</div>
				{/each}
			</div>
		{/if}
	</div>
	{#if promoteFor}
		<!-- svelte-ignore a11y_click_events_have_key_events -->
		<div class="promo-overlay" onclick={(e) => e.target === e.currentTarget && (promoteFor = null)} role="presentation">
			<div class="promo-modal" role="dialog" aria-modal="true">
				<h5 class="mb-3">İrəli çək: {listingTitle(promoteFor)}</h5>
				{#if promoteMsg}
					<div class="alert alert-info mb-0">{promoteMsg}</div>
				{:else if packages.length === 0}
					<p class="text-muted mb-0">Hazırda paket yoxdur.</p>
				{:else}
					<div class="d-flex flex-column gap-2 mb-3">
						{#each packages as pkg (pkg.id)}
							<label class="promo-option" class:active={selectedPackage === pkg.id}>
								<input type="radio" name="pkg" value={pkg.id} checked={selectedPackage === pkg.id}
								       onchange={() => (selectedPackage = pkg.id)} />
								<span>{packageName(pkg)}</span>
								<span class="ms-auto fw-bold">{Number(pkg.price).toFixed(2)} ₼</span>
							</label>
						{/each}
					</div>
					<button class="theme-btn" type="button" disabled={!selectedPackage || promoting} onclick={submitPromote}>
						{promoting ? 'Göndərilir…' : 'Sifariş et'}
					</button>
				{/if}
			</div>
		</div>
	{/if}
</section>

<style>
	.promo-overlay { position: fixed; inset: 0; z-index: 1080; background: rgba(15,23,42,.55); display: flex; align-items: center; justify-content: center; padding: 16px; }
	.promo-modal { width: min(440px, 100%); background: #fff; border-radius: 16px; padding: 22px; box-shadow: 0 24px 60px rgba(15,23,42,.3); }
	.promo-option { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1.5px solid #e6e9f0; border-radius: 12px; cursor: pointer; }
	.promo-option.active { border-color: var(--theme); background: color-mix(in srgb, var(--theme) 8%, #fff); }
</style>
