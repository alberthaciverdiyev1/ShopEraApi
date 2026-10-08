<script lang="ts">
	import { onMount } from 'svelte';
	import { locale } from '$lib/i18n';
	import { isLoggedIn, user } from '$lib/services/auth';
	import { createGuestListing, createListing, buildListingForm, type ApiListing } from '$lib/services/listings';
	import { loadCategories, categories, categoryName, type Category, type ApiCategory } from '$lib/services/categories';
	import { loadDeliveryCities, deliveryCities } from '$lib/services/delivery';

	type Picked = { id: number; name: string };

	let step = $state<1 | 2>(1);
	let picked = $state<Picked | null>(null);
	let activeCategory = $state<Category | null>(null);

	// Step 2 fields
	let title = $state('');
	let description = $state('');
	let cityId = $state('');
	let condition = $state('used');
	let price = $state('');
	let contactName = $state('');
	let contactPhone = $state('');
	let contactEmail = $state('');

	// Images
	let files = $state<File[]>([]);
	let previews = $state<string[]>([]);
	let dragging = $state(false);

	// Result
	let submitting = $state(false);
	let error = $state<string | null>(null);
	let published = $state<ApiListing | null>(null);
	let manageUrl = $state<string | null>(null);

	function pick(category: Category | ApiCategory) {
		picked = { id: category.id, name: categoryName(category, $locale) };
		step = 2;
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}

	function backToCategory() {
		step = 1;
		activeCategory = null;
	}

	function addFiles(list: FileList | null) {
		if (!list) return;
		const images = Array.from(list).filter((f) => f.type.startsWith('image/'));
		for (const file of images) {
			if (files.length >= 10) break;
			files = [...files, file];
			previews = [...previews, URL.createObjectURL(file)];
		}
	}

	function removeImage(index: number) {
		URL.revokeObjectURL(previews[index]);
		files = files.filter((_, i) => i !== index);
		previews = previews.filter((_, i) => i !== index);
	}

	function onDrop(event: DragEvent) {
		event.preventDefault();
		dragging = false;
		addFiles(event.dataTransfer?.files ?? null);
	}

	async function onSubmit(event: SubmitEvent) {
		event.preventDefault();
		if (!picked) return;
		error = null;
		submitting = true;

		const fields: Record<string, string | number> = {
			title,
			description,
			category_id: picked.id,
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
				published = (await createListing(form)).listing;
			} else {
				const res = await createGuestListing(form);
				published = res.listing;
				manageUrl = res.manage_url;
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
	<div class="container" style="max-width: 900px;">
		<h2 class="mb-1">Elan yerləşdir</h2>
		<p class="text-muted mb-4">
			{step === 1 ? 'Nə satmaq istədiyinizi seçin' : 'Elanın detallarını doldurun'}
		</p>

		<!-- Steps indicator -->
		<div class="steps mb-4">
			<div class="step" class:active={step === 1} class:done={step === 2}>
				<span class="dot">1</span> Kateqoriya
			</div>
			<div class="bar"></div>
			<div class="step" class:active={step === 2}>
				<span class="dot">2</span> Elan məlumatları
			</div>
		</div>

		{#if published}
			<div class="alert alert-success">
				<p class="fw-bold mb-1">Elan uğurla yerləşdirildi! 🎉</p>
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
		{:else if step === 1}
			<!-- STEP 1: category picker -->
			{#if $categories.length === 0}
				<p class="text-muted">Kateqoriyalar yüklənir…</p>
			{:else}
				<div class="picker">
					<div class="parents">
						{#each $categories as category (category.id)}
							<button type="button" class="parent" class:active={activeCategory?.id === category.id}
							        onmouseenter={() => (activeCategory = category)}
							        onfocus={() => (activeCategory = category)}
							        onclick={() => (category.children.length ? (activeCategory = category) : pick(category))}>
								<span>{categoryName(category, $locale)}</span>
								{#if category.children.length}<i class="fas fa-angle-right"></i>{/if}
							</button>
						{/each}
					</div>

					<div class="children">
						{#if activeCategory}
							<p class="fw-semibold mb-2">{categoryName(activeCategory, $locale)}</p>
							{#if activeCategory.children.length}
								<div class="chips">
									{#each activeCategory.children as child (child.id)}
										<button type="button" class="chip"
										        onclick={() => (child.children.length ? (activeCategory = child) : pick(child))}>
											{categoryName(child, $locale)}
											{#if child.children.length}<i class="fas fa-angle-right ms-1"></i>{/if}
										</button>
									{/each}
								</div>
								<button type="button" class="btn btn-sm btn-outline-secondary mt-3"
								        onclick={() => pick(activeCategory as Category)}>
									Bu kateqoriyanı seç
								</button>
							{:else}
								<button type="button" class="theme-btn" onclick={() => pick(activeCategory as Category)}>
									Bu kateqoriyanı seç
								</button>
							{/if}
						{:else}
							<p class="text-muted mb-0">Kateqoriyanın üzərinə gəlin (hover) → alt kateqoriyalar burada açılacaq.</p>
						{/if}
					</div>
				</div>
			{/if}
		{:else}
			<!-- STEP 2: listing details -->
			<div class="d-flex align-items-center justify-content-between mb-3 p-3 rounded"
			     style="background:#f7f8fb;">
				<span>Kateqoriya: <strong>{picked?.name}</strong></span>
				<button type="button" class="btn btn-sm btn-outline-secondary" onclick={backToCategory}>Dəyiş</button>
			</div>

			{#if error}<div class="alert alert-danger">{error}</div>{/if}

			<form class="row g-3" onsubmit={onSubmit}>
				<div class="col-12">
					<label class="form-label" for="l-title">Başlıq *</label>
					<input id="l-title" class="form-control" bind:value={title} required maxlength="255" />
				</div>
				<div class="col-12">
					<label class="form-label" for="l-desc">Təsvir *</label>
					<textarea id="l-desc" class="form-control" rows="5" bind:value={description} required></textarea>
				</div>
				<div class="col-md-6">
					<label class="form-label" for="l-city">Şəhər *</label>
					<select id="l-city" class="form-select" bind:value={cityId} required>
						<option value="">Seçin…</option>
						{#each $deliveryCities as city (city.id)}
							<option value={city.id}>{city.name}</option>
						{/each}
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label" for="l-cond">Vəziyyət *</label>
					<select id="l-cond" class="form-select" bind:value={condition}>
						<option value="new">Yeni</option>
						<option value="used">İşlənmiş</option>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label" for="l-price">Qiymət (₼) *</label>
					<input id="l-price" class="form-control" type="number" min="0" step="0.01" bind:value={price} required />
				</div>

				<!-- Image picker -->
				<div class="col-12">
					<label class="form-label">Şəkillər <span class="text-muted">(maks. 10)</span></label>
					<label class="dropzone" class:is-drag={dragging}
					       ondragover={(e) => { e.preventDefault(); dragging = true; }}
					       ondragleave={() => (dragging = false)}
					       ondrop={onDrop}>
						<input type="file" accept="image/*" multiple hidden
						       onchange={(e) => addFiles(e.currentTarget.files)} />
						<div class="dz-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
						<div><strong>Şəkilləri bura sürükləyin</strong> və ya <span class="link">seçin</span></div>
						<div class="text-muted small">JPG, PNG, WEBP — hər biri maks. 8 MB</div>
					</label>

					{#if previews.length}
						<div class="thumbs">
							{#each previews as src, i (src)}
								<div class="thumb">
									<img {src} alt={`Şəkil ${i + 1}`} />
									<button type="button" class="remove" onclick={() => removeImage(i)} aria-label="Sil">×</button>
								</div>
							{/each}
						</div>
					{/if}
				</div>

				{#if !$isLoggedIn}
					<div class="col-12"><hr /><p class="fw-semibold mb-0">Əlaqə məlumatları</p></div>
					<div class="col-md-4">
						<label class="form-label" for="l-name">Adınız *</label>
						<input id="l-name" class="form-control" bind:value={contactName} required />
					</div>
					<div class="col-md-4">
						<label class="form-label" for="l-phone">Telefon *</label>
						<input id="l-phone" class="form-control" bind:value={contactPhone} required placeholder="+994..." />
					</div>
					<div class="col-md-4">
						<label class="form-label" for="l-email">E-poçt</label>
						<input id="l-email" class="form-control" type="email" bind:value={contactEmail} />
					</div>
				{:else}
					<p class="col-12 text-muted small mb-0">Əlaqə məlumatları hesabınızdan götürüləcək ({$user?.name}).</p>
				{/if}

				<div class="col-12 d-flex gap-2">
					<button class="theme-btn" type="submit" disabled={submitting}>
						{submitting ? 'Yerləşdirilir…' : 'Yerləşdir'}
					</button>
					<button class="btn btn-outline-secondary" type="button" onclick={backToCategory}>Geri</button>
				</div>
			</form>
		{/if}
	</div>
</section>

<style>
	.steps { display: flex; align-items: center; gap: 12px; }
	.steps .step { display: flex; align-items: center; gap: 8px; color: #94a3b8; font-weight: 600; font-size: 14px; }
	.steps .step .dot { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: #e2e8f0; color: #64748b; font-size: 13px; }
	.steps .step.active { color: #0f172a; }
	.steps .step.active .dot { background: var(--theme); color: #fff; }
	.steps .step.done .dot { background: #16a34a; color: #fff; }
	.steps .bar { flex: 1; height: 2px; background: #e2e8f0; }

	.picker { display: grid; grid-template-columns: 280px 1fr; gap: 20px; border: 1px solid #e6e9f0; border-radius: 16px; padding: 16px; background: #fff; }
	@media (max-width: 767.98px) { .picker { grid-template-columns: 1fr; } .children { border-left: 0 !important; padding-left: 0 !important; } }
	.parents { display: flex; flex-direction: column; gap: 4px; max-height: 420px; overflow-y: auto; }
	.parent { display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%; padding: 10px 12px; border: 0; border-radius: 10px; background: transparent; color: #0f172a; font-weight: 600; text-align: left; cursor: pointer; }
	.parent:hover, .parent.active { background: color-mix(in srgb, var(--theme) 10%, #fff); color: var(--theme); }
	.children { border-left: 1px solid #eef1f6; padding-left: 20px; min-height: 120px; }
	.chips { display: flex; flex-wrap: wrap; gap: 8px; }
	.chip { padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 999px; background: #fff; font-weight: 600; font-size: 14px; cursor: pointer; }
	.chip:hover { border-color: var(--theme); color: var(--theme); }

	.dropzone { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 28px; border: 2px dashed #cbd5e1; border-radius: 14px; background: #f8fafc; text-align: center; cursor: pointer; transition: all .15s ease; }
	.dropzone:hover, .dropzone.is-drag { border-color: var(--theme); background: color-mix(in srgb, var(--theme) 6%, #fff); }
	.dz-icon { font-size: 30px; color: var(--theme); }
	.dropzone .link { color: var(--theme); text-decoration: underline; font-weight: 600; }

	.thumbs { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 10px; margin-top: 12px; }
	.thumb { position: relative; aspect-ratio: 1; border-radius: 12px; overflow: hidden; border: 1px solid #e6e9f0; }
	.thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
	.thumb .remove { position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border: 0; border-radius: 50%; background: rgba(15,23,42,.7); color: #fff; font-size: 16px; line-height: 1; cursor: pointer; }
</style>
