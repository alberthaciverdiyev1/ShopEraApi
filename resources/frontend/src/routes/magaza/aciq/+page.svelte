<script lang="ts">
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { isLoggedIn } from '$lib/services/auth';
	import { createVendor, fetchMyVendor, buildVendorForm } from '$lib/services/vendors';

	let name = $state('');
	let description = $state('');
	let phone = $state('');
	let address = $state('');
	let logo = $state<File | null>(null);
	let submitting = $state(false);
	let error = $state<string | null>(null);
	let checking = $state(true);

	onMount(async () => {
		if (!$isLoggedIn) {
			goto('/login');
			return;
		}
		const vendor = await fetchMyVendor();
		if (vendor) {
			goto('/magaza/panel');
			return;
		}
		checking = false;
	});

	async function onSubmit(event: SubmitEvent) {
		event.preventDefault();
		error = null;
		submitting = true;
		try {
			const form = buildVendorForm({ name, description, phone, address }, logo);
			const vendor = await createVendor(form);
			goto(`/magaza/panel`);
			return vendor;
		} catch (e) {
			error = e instanceof Error ? e.message : 'Xəta baş verdi.';
		} finally {
			submitting = false;
		}
	}
</script>

<section class="section-padding fix">
	<div class="container" style="max-width: 720px;">
		<h2 class="mb-1">Mağaza aç</h2>
		<p class="text-muted mb-4">Öz mağazanızı yaradın və elanlarınızı bir brend altında idarə edin.</p>

		{#if checking}
			<p class="text-muted">Yoxlanılır…</p>
		{:else}
			{#if error}<div class="alert alert-danger">{error}</div>{/if}
			<form class="row g-3" onsubmit={onSubmit}>
				<div class="col-md-8">
					<label class="form-label" for="v-name">Mağaza adı *</label>
					<input id="v-name" class="form-control" bind:value={name} required maxlength="120" />
				</div>
				<div class="col-md-4">
					<label class="form-label" for="v-phone">Telefon</label>
					<input id="v-phone" class="form-control" bind:value={phone} placeholder="+994..." />
				</div>
				<div class="col-12">
					<label class="form-label" for="v-desc">Təsvir</label>
					<textarea id="v-desc" class="form-control" rows="4" bind:value={description}></textarea>
				</div>
				<div class="col-12">
					<label class="form-label" for="v-addr">Ünvan</label>
					<input id="v-addr" class="form-control" bind:value={address} />
				</div>
				<div class="col-12">
					<label class="form-label" for="v-logo">Loqo</label>
					<input id="v-logo" class="form-control" type="file" accept="image/*"
					       onchange={(e) => (logo = (e.currentTarget as HTMLInputElement).files?.[0] ?? null)} />
				</div>
				<div class="col-12">
					<button class="theme-btn" type="submit" disabled={submitting}>
						{submitting ? 'Yaradılır…' : 'Mağaza yarat'}
					</button>
				</div>
			</form>
		{/if}
	</div>
</section>
