<script lang="ts">
	import { onMount } from 'svelte';
	import { page } from '$app/state';
	import {
		fetchManagedListing,
		updateManagedListing,
		deleteManagedListing,
		buildListingForm,
		listingTitle,
		listingImage,
		type ApiListing
	} from '$lib/services/listings';

	const token = $derived(page.params.token ?? '');

	let listing = $state<ApiListing | null>(null);
	let loading = $state(true);
	let error = $state<string | null>(null);
	let deleted = $state(false);
	let saving = $state(false);

	let title = $state('');
	let description = $state('');
	let price = $state('');
	let condition = $state('used');
	let files = $state<File[]>([]);

	function fill(l: ApiListing) {
		title = listingTitle(l);
		description = (l.description?.az ?? l.description?.en ?? '') as string;
		price = String(l.price ?? '');
		condition = l.condition ?? 'used';
	}

	async function save(event: SubmitEvent) {
		event.preventDefault();
		error = null;
		saving = true;
		try {
			const form = buildListingForm({ title, description, price, condition }, files);
			await updateManagedListing(token, form);
			listing = await fetchManagedListing(token);
			fill(listing);
		} catch (e) {
			error = e instanceof Error ? e.message : 'Yadda saxlanıla bilmədi.';
		} finally {
			saving = false;
		}
	}

	async function remove() {
		if (!confirm('Elan silinsin?')) return;
		await deleteManagedListing(token);
		deleted = true;
	}

	onMount(async () => {
		try {
			listing = await fetchManagedListing(token);
			fill(listing);
		} catch {
			error = 'İdarə linki etibarsızdır və ya elan silinib.';
		} finally {
			loading = false;
		}
	});
</script>

<section class="section-padding fix">
	<div class="container" style="max-width: 760px;">
		<h2 class="mb-4">Elanı idarə et</h2>

		{#if loading}
			<p class="text-muted">Yüklənir…</p>
		{:else if deleted}
			<div class="alert alert-success">Elan silindi.</div>
		{:else if error && !listing}
			<div class="alert alert-danger">{error}</div>
		{:else if listing}
			{#if error}<div class="alert alert-danger">{error}</div>{/if}
			<img src={listingImage(listing)} alt={listingTitle(listing)} class="img-fluid rounded mb-3"
			     style="max-height:280px;object-fit:cover;">

			<form class="row g-3" onsubmit={save}>
				<div class="col-12">
					<label class="form-label">Başlıq</label>
					<input class="form-control" bind:value={title} required />
				</div>
				<div class="col-12">
					<label class="form-label">Təsvir</label>
					<textarea class="form-control" rows="4" bind:value={description} required></textarea>
				</div>
				<div class="col-md-6">
					<label class="form-label">Qiymət (₼)</label>
					<input class="form-control" type="number" min="0" step="0.01" bind:value={price} required />
				</div>
				<div class="col-md-6">
					<label class="form-label">Vəziyyət</label>
					<select class="form-select" bind:value={condition}>
						<option value="new">Yeni</option>
						<option value="used">İşlənmiş</option>
					</select>
				</div>
				<div class="col-12">
					<label class="form-label">Yeni şəkillər</label>
					<input class="form-control" type="file" accept="image/*" multiple
					       onchange={(e) => (files = Array.from((e.currentTarget as HTMLInputElement).files ?? []))} />
				</div>
				<div class="col-12 d-flex gap-2">
					<button class="theme-btn" type="submit" disabled={saving}>
						{saving ? 'Yadda saxlanılır…' : 'Yadda saxla'}
					</button>
					<button class="btn btn-outline-danger" type="button" onclick={remove}>Sil</button>
				</div>
			</form>
		{/if}
	</div>
</section>
