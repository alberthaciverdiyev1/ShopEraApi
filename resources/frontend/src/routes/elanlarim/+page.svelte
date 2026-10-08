<script lang="ts">
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { isLoggedIn } from '$lib/services/auth';
	import { fetchMyListings, deleteListing, listingTitle, listingImage, type ApiListing } from '$lib/services/listings';

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
								<button class="btn btn-outline-danger btn-sm mt-auto" type="button"
								        onclick={() => remove(listing.id)}>Sil</button>
							</div>
						</div>
					</div>
				{/each}
			</div>
		{/if}
	</div>
</section>
