<script lang="ts">
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { isLoggedIn } from '$lib/services/auth';
	import { fetchMyVendor, updateVendor, buildVendorForm, vendorName, vendorDescription, type ApiVendor } from '$lib/services/vendors';
	import { fetchMyListings, deleteListing, listingTitle, listingImage, type ApiListing } from '$lib/services/listings';

	let vendor = $state<ApiVendor | null>(null);
	let listings = $state<ApiListing[]>([]);
	let loading = $state(true);

	let name = $state('');
	let description = $state('');
	let phone = $state('');
	let address = $state('');
	let logo = $state<File | null>(null);
	let saving = $state(false);
	let saved = $state(false);
	let error = $state<string | null>(null);

	const stats = $derived({
		total: listings.length,
		promoted: listings.filter((l) => l.is_promoted).length,
		premium: listings.filter((l) => l.is_premium).length
	});

	function fill(v: ApiVendor) {
		name = vendorName(v);
		description = vendorDescription(v);
		phone = v.phone ?? '';
		address = v.address ?? '';
	}

	async function save(event: SubmitEvent) {
		event.preventDefault();
		error = null;
		saving = true;
		saved = false;
		try {
			const form = buildVendorForm({ name, description, phone, address }, logo);
			vendor = await updateVendor(form);
			fill(vendor);
			saved = true;
		} catch (e) {
			error = e instanceof Error ? e.message : 'Yadda saxlanıla bilmədi.';
		} finally {
			saving = false;
		}
	}

	async function remove(id: number) {
		if (!confirm('Elan silinsin?')) return;
		await deleteListing(id);
		listings = listings.filter((l) => l.id !== id);
	}

	onMount(async () => {
		if (!$isLoggedIn) {
			goto('/login');
			return;
		}
		vendor = await fetchMyVendor();
		if (!vendor) {
			goto('/magaza/aciq');
			return;
		}
		fill(vendor);
		listings = await fetchMyListings();
		loading = false;
	});
</script>

<section class="section-padding fix">
	<div class="container" style="max-width: 960px;">
		{#if loading}
			<p class="text-muted">Yüklənir…</p>
		{:else if vendor}
			<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
				<h2 class="mb-0">{vendorName(vendor)} <span class="text-muted fs-6">— Mağaza paneli</span></h2>
				<div class="d-flex gap-2">
					<a class="btn btn-outline-secondary" href={`/magaza/${vendor.slug}`} target="_blank">Mağaza səhifəsi</a>
					<a class="theme-btn" href="/elan/ver">Yeni elan</a>
				</div>
			</div>

			<div class="row g-3 mb-4">
				<div class="col-6 col-md-3"><div class="p-3 rounded bg-white border"><div class="text-muted small">Elanlar</div><div class="fs-4 fw-bold">{stats.total}</div></div></div>
				<div class="col-6 col-md-3"><div class="p-3 rounded bg-white border"><div class="text-muted small">İrəli çəkilmiş</div><div class="fs-4 fw-bold">{stats.promoted}</div></div></div>
				<div class="col-6 col-md-3"><div class="p-3 rounded bg-white border"><div class="text-muted small">Premium</div><div class="fs-4 fw-bold">{stats.premium}</div></div></div>
			</div>

			<div class="row g-4">
				<div class="col-lg-5">
					<form class="p-4 bg-white border rounded" onsubmit={save}>
						<h6 class="fw-bold mb-3">Mağaza məlumatları</h6>
						{#if error}<div class="alert alert-danger py-2">{error}</div>{/if}
						{#if saved}<div class="alert alert-success py-2">Yadda saxlanıldı.</div>{/if}
						<div class="mb-3">
							<label class="form-label" for="p-name">Ad</label>
							<input id="p-name" class="form-control" bind:value={name} required />
						</div>
						<div class="mb-3">
							<label class="form-label" for="p-desc">Təsvir</label>
							<textarea id="p-desc" class="form-control" rows="3" bind:value={description}></textarea>
						</div>
						<div class="mb-3">
							<label class="form-label" for="p-phone">Telefon</label>
							<input id="p-phone" class="form-control" bind:value={phone} />
						</div>
						<div class="mb-3">
							<label class="form-label" for="p-addr">Ünvan</label>
							<input id="p-addr" class="form-control" bind:value={address} />
						</div>
						<div class="mb-3">
							<label class="form-label" for="p-logo">Loqo</label>
							<input id="p-logo" class="form-control" type="file" accept="image/*"
							       onchange={(e) => (logo = (e.currentTarget as HTMLInputElement).files?.[0] ?? null)} />
						</div>
						<button class="theme-btn" type="submit" disabled={saving}>{saving ? 'Saxlanılır…' : 'Yadda saxla'}</button>
					</form>
				</div>

				<div class="col-lg-7">
					<div class="p-4 bg-white border rounded">
						<h6 class="fw-bold mb-3">Elanlarım</h6>
						{#if listings.length === 0}
							<p class="text-muted mb-0">Hələ elanınız yoxdur.</p>
						{:else}
							<div class="d-flex flex-column gap-2">
								{#each listings as listing (listing.id)}
									<div class="d-flex align-items-center gap-3 border-bottom pb-2">
										<img src={listingImage(listing)} alt="" style="width:52px;height:52px;object-fit:cover;border-radius:8px;">
										<div class="flex-grow-1 min-w-0">
											<p class="mb-0 fw-semibold text-truncate">{listingTitle(listing)}</p>
											<p class="mb-0 small text-primary">{Number(listing.price ?? 0).toFixed(2)} ₼</p>
										</div>
										<button class="btn btn-sm btn-outline-danger" type="button" onclick={() => remove(listing.id)}>Sil</button>
									</div>
								{/each}
							</div>
						{/if}
					</div>
				</div>
			</div>
		{/if}
	</div>
</section>
