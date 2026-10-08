<script lang="ts">
	import { onMount } from 'svelte';
	import { isLoggedIn, user } from '$lib/services/auth';
	import { createGuestListing, createListing, buildListingForm, type ApiListing } from '$lib/services/listings';
	import { loadCategories, categories, categoryName } from '$lib/services/categories';
	import { loadDeliveryCities, deliveryCities } from '$lib/services/delivery';

	let title = $state('');
	let description = $state('');
	let categoryId = $state('');
	let cityId = $state('');
	let condition = $state('used');
	let price = $state('');
	let files = $state<File[]>([]);

	let contactName = $state('');
	let contactPhone = $state('');
	let contactEmail = $state('');

	let submitting = $state(false);
	let error = $state<string | null>(null);
	let publishedListing = $state<ApiListing | null>(null);
	let manageUrl = $state<string | null>(null);

	function onFiles(event: Event) {
		const input = event.currentTarget as HTMLInputElement;
		files = Array.from(input.files ?? []);
	}

	async function onSubmit(event: SubmitEvent) {
		event.preventDefault();
		error = null;
		submitting = true;

		const fields: Record<string, string | number> = {
			title,
			description,
			category_id: categoryId,
			city_id: cityId,
			condition,
			price
		};

		if (!$isLoggedIn) {
			fields.contact_name = contactName;
			fields.contact_phone = contactPhone;
			if (contactEmail) fields.contact_email = contactEmail;
		}

		try {
			const form = buildListingForm(fields, files);
			if ($isLoggedIn) {
				const { listing } = await createListing(form);
				publishedListing = listing;
			} else {
				const { listing, manage_url } = await createGuestListing(form);
				publishedListing = listing;
				manageUrl = manage_url;
			}
		} catch (e) {
			error = e instanceof Error ? e.message : 'Xəta baş verdi.';
		} finally {
			submitting = false;
		}
	}

	onMount(() => {
		loadCategories();
		loadDeliveryCities();
	});
</script>

<section class="section-padding fix">
	<div class="container" style="max-width: 820px;">
		<h2 class="mb-4">Elan yerləşdir</h2>

		{#if publishedListing}
			<div class="alert alert-success">
				<p class="fw-bold mb-1">Elan uğurla yerləşdirildi!</p>
				{#if manageUrl}
					<p class="mb-2">Bu elanı sonradan <strong>redaktə və ya silmək</strong> üçün aşağıdaki linki saxlayın (login tələb olunmur):</p>
					<div class="input-group mb-2">
						<input class="form-control" readonly value={manageUrl} />
						<a class="btn btn-outline-secondary" href={manageUrl}>Aç</a>
					</div>
					<p class="text-danger small mb-0">⚠️ Bu linki itirsəniz, elanı idarə edə bilməyəcəksiniz.</p>
				{:else}
					<a href="/elanlarim" class="btn btn-primary mt-2">Elanlarım</a>
				{/if}
			</div>
		{:else}
			{#if error}
				<div class="alert alert-danger">{error}</div>
			{/if}

			<form class="row g-3" onsubmit={onSubmit}>
				<div class="col-12">
					<label class="form-label">Başlıq *</label>
					<input class="form-control" bind:value={title} required maxlength="255" />
				</div>
				<div class="col-12">
					<label class="form-label">Təsvir *</label>
					<textarea class="form-control" rows="5" bind:value={description} required></textarea>
				</div>
				<div class="col-md-6">
					<label class="form-label">Kateqoriya *</label>
					<select class="form-select" bind:value={categoryId} required>
						<option value="">Seçin…</option>
						{#each $categories as category (category.id)}
							<option value={category.id}>{categoryName(category)}</option>
						{/each}
					</select>
				</div>
				<div class="col-md-6">
					<label class="form-label">Şəhər *</label>
					<select class="form-select" bind:value={cityId} required>
						<option value="">Seçin…</option>
						{#each $deliveryCities as city (city.id)}
							<option value={city.id}>{city.name}</option>
						{/each}
					</select>
				</div>
				<div class="col-md-6">
					<label class="form-label">Vəziyyət *</label>
					<select class="form-select" bind:value={condition}>
						<option value="new">Yeni</option>
						<option value="used">İşlənmiş</option>
					</select>
				</div>
				<div class="col-md-6">
					<label class="form-label">Qiymət (₼) *</label>
					<input class="form-control" type="number" min="0" step="0.01" bind:value={price} required />
				</div>
				<div class="col-12">
					<label class="form-label">Şəkillər</label>
					<input class="form-control" type="file" accept="image/*" multiple onchange={onFiles} />
				</div>

				{#if !$isLoggedIn}
					<div class="col-12"><hr /><p class="fw-semibold mb-0">Əlaqə məlumatları</p></div>
					<div class="col-md-4">
						<label class="form-label">Adınız *</label>
						<input class="form-control" bind:value={contactName} required />
					</div>
					<div class="col-md-4">
						<label class="form-label">Telefon *</label>
						<input class="form-control" bind:value={contactPhone} required placeholder="+994..." />
					</div>
					<div class="col-md-4">
						<label class="form-label">E-poçt</label>
						<input class="form-control" type="email" bind:value={contactEmail} />
					</div>
				{:else}
					<p class="col-12 text-muted small mb-0">Əlaqə məlumatları hesabınızdan götürüləcək ({$user?.name}).</p>
				{/if}

				<div class="col-12">
					<button class="theme-btn" type="submit" disabled={submitting}>
						{submitting ? 'Yerləşdirilir…' : 'Yerləşdir'}
					</button>
				</div>
			</form>
		{/if}
	</div>
</section>
