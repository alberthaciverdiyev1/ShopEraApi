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

	let title = $state('');
	let description = $state('');
	let cityId = $state('');
	let condition = $state('used');
	let price = $state('');
	let contactName = $state('');
	let contactPhone = $state('');
	let contactEmail = $state('');

	let files = $state<File[]>([]);
	let previews = $state<string[]>([]);
	let dragging = $state(false);

	let submitting = $state(false);
	let error = $state<string | null>(null);
	let published = $state<ApiListing | null>(null);
	let manageUrl = $state<string | null>(null);
	let copied = $state(false);

	function initial(name: string): string {
		return name?.trim()?.charAt(0)?.toUpperCase() ?? '?';
	}

	function pick(category: Category | ApiCategory) {
		picked = { id: category.id, name: categoryName(category, $locale) };
		step = 2;
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}

	function backToCategory() {
		step = 1;
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

	async function copyLink() {
		if (!manageUrl) return;
		try {
			await navigator.clipboard.writeText(manageUrl);
			copied = true;
			setTimeout(() => (copied = false), 2000);
		} catch {
			/* clipboard may be blocked */
		}
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
			window.scrollTo({ top: 0, behavior: 'smooth' });
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

<section class="elan-page">
	<div class="container" style="max-width: 940px;">
		<header class="hero">
			<span class="hero-badge"><i class="fa-solid fa-bullhorn"></i> Pulsuz elan</span>
			<h1>Elan yerləşdir</h1>
			<p>Bir neçə addımda elanınızı dərc edin — qeydiyyat tələb olunmur.</p>
		</header>

		<div class="steps">
			<div class="step" class:active={step === 1} class:done={step === 2}>
				<span class="dot">{step === 2 ? '✓' : '1'}</span>
				<span>Kateqoriya</span>
			</div>
			<div class="bar" class:filled={step === 2}></div>
			<div class="step" class:active={step === 2}>
				<span class="dot">2</span>
				<span>Elan məlumatları</span>
			</div>
		</div>

		{#if published}
			<div class="success-card">
				<div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
				<h3>Elan uğurla yerləşdirildi!</h3>
				{#if manageUrl}
					<p>Bu elanı sonradan <strong>redaktə və ya silmək</strong> üçün aşağıdaki linki saxlayın — login tələb olunmur.</p>
					<div class="copy-row">
						<input class="form-control" readonly value={manageUrl} />
						<button class="btn btn-outline-secondary" type="button" onclick={copyLink}>
							{copied ? 'Kopyalandı ✓' : 'Kopyala'}
						</button>
						<a class="theme-btn" href={manageUrl}>Aç</a>
					</div>
					<p class="warn"><i class="fa-solid fa-triangle-exclamation"></i> Bu linki itirsəniz, elanı idarə edə bilməyəcəksiniz.</p>
				{:else}
					<a href="/elanlarim" class="theme-btn">Elanlarıma keç</a>
				{/if}
			</div>
		{:else if step === 1}
			{#if $categories.length === 0}
				<p class="text-muted">Kateqoriyalar yüklənir…</p>
			{:else}
				<p class="section-hint">Elanınızın uyğun olduğu kateqoriyanı seçin</p>

				<div class="cat-grid">
					{#each $categories as category (category.id)}
						<button type="button" class="cat-card" class:active={activeCategory?.id === category.id}
						        onmouseenter={() => (activeCategory = category)}
						        onfocus={() => (activeCategory = category)}
						        onclick={() => (category.children.length ? (activeCategory = category) : pick(category))}>
							{#if category.image}
								<span class="cat-thumb" style={`background-image:url('${category.image}')`}></span>
							{:else}
								<span class="cat-thumb placeholder">{initial(categoryName(category, $locale))}</span>
							{/if}
							<span class="cat-name">{categoryName(category, $locale)}</span>
							{#if category.children.length}
								<span class="cat-count">{category.children.length} alt kateqoriya</span>
							{/if}
						</button>
					{/each}
				</div>

				{#if activeCategory}
					<div class="subpanel">
						<div class="subpanel-head">
							<span class="dot-sm"></span>
							<strong>{categoryName(activeCategory, $locale)}</strong>
							{#if activeCategory.children.length}
								<button type="button" class="select-parent" onclick={() => pick(activeCategory as Category)}>
									bu kateqoriyanı seç
								</button>
							{/if}
						</div>

						{#if activeCategory.children.length}
							<div class="sub-grid">
								{#each activeCategory.children as child (child.id)}
									<button type="button" class="sub-card"
									        onclick={() => (child.children.length ? (activeCategory = child) : pick(child))}>
										<span>{categoryName(child, $locale)}</span>
										{#if child.children.length}<i class="fas fa-chevron-right"></i>{/if}
									</button>
								{/each}
							</div>
						{:else}
							<button type="button" class="theme-btn" onclick={() => pick(activeCategory as Category)}>
								Bu kateqoriyanı seç
							</button>
						{/if}
					</div>
				{:else}
					<p class="section-hint mt-3 mb-0">Kateqoriyanın üzərinə gəlin — alt kateqoriyalar burada açılacaq.</p>
				{/if}
			{/if}
		{:else}
			<div class="picked-bar">
				<div>
					<span class="muted">Kateqoriya</span>
					<strong>{picked?.name}</strong>
				</div>
				<button type="button" class="btn btn-sm btn-outline-secondary" onclick={backToCategory}>Dəyiş</button>
			</div>

			{#if error}<div class="alert alert-danger">{error}</div>{/if}

			<form class="row g-4" onsubmit={onSubmit}>
				<div class="col-12 form-section">
					<h6 class="section-title">Əsas məlumat</h6>
					<div class="row g-3">
						<div class="col-12">
							<label class="form-label" for="l-title">Başlıq *</label>
							<input id="l-title" class="form-control" bind:value={title} required maxlength="255"
							       placeholder="Məsələn: iPhone 15 Pro 256GB" />
						</div>
						<div class="col-12">
							<label class="form-label" for="l-desc">Təsvir *</label>
							<textarea id="l-desc" class="form-control" rows="5" bind:value={description} required
							          placeholder="Vəziyyəti, xüsusiyyətləri və s. yazın…"></textarea>
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
					</div>
				</div>

				<div class="col-12 form-section">
					<h6 class="section-title">Şəkillər <span class="muted">(maks. 10)</span></h6>
					<label class="dropzone" class:is-drag={dragging}
					       ondragover={(e) => { e.preventDefault(); dragging = true; }}
					       ondragleave={() => (dragging = false)}
					       ondrop={onDrop}>
						<input type="file" accept="image/*" multiple hidden
						       onchange={(e) => addFiles(e.currentTarget.files)} />
						<div class="dz-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
						<div><strong>Şəkilləri bura sürükləyin</strong> və ya <span class="link">fayl seçin</span></div>
						<div class="muted small">JPG, PNG, WEBP — hər biri maks. 8 MB</div>
					</label>

					{#if previews.length}
						<div class="thumbs">
							{#each previews as src, i (src)}
								<div class="thumb">
									{#if i === 0}<span class="cover-tag">Əsas</span>{/if}
									<img {src} alt={`Şəkil ${i + 1}`} />
									<button type="button" class="remove" onclick={() => removeImage(i)} aria-label="Sil">×</button>
								</div>
							{/each}
						</div>
					{/if}
				</div>

				<div class="col-12 form-section">
					<h6 class="section-title">Əlaqə</h6>
					{#if !$isLoggedIn}
						<div class="row g-3">
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
						</div>
					{:else}
						<p class="muted mb-0">Əlaqə məlumatları hesabınızdan götürüləcək ({$user?.name}).</p>
					{/if}
				</div>

				<div class="col-12 actions">
					<button class="theme-btn" type="submit" disabled={submitting}>
						{submitting ? 'Yerləşdirilir…' : 'Elanı yerləşdir'}
					</button>
					<button class="btn btn-outline-secondary" type="button" onclick={backToCategory}>Geri</button>
				</div>
			</form>
		{/if}
	</div>
</section>

<style>
	.elan-page { padding: 40px 0 70px; background: #f7f8fb; }

	.hero { text-align: center; margin-bottom: 26px; }
	.hero-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; background: color-mix(in srgb, var(--theme) 12%, #fff); color: var(--theme); font-weight: 700; font-size: 13px; }
	.hero h1 { margin: 14px 0 6px; font-size: 34px; font-weight: 850; color: #0f172a; }
	.hero p { color: #64748b; margin: 0; }

	.steps { display: flex; align-items: center; gap: 12px; max-width: 560px; margin: 0 auto 30px; }
	.steps .step { display: flex; align-items: center; gap: 8px; color: #94a3b8; font-weight: 700; font-size: 14px; white-space: nowrap; }
	.steps .dot { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: #e2e8f0; color: #64748b; font-size: 13px; font-weight: 700; }
	.steps .step.active { color: #0f172a; }
	.steps .step.active .dot { background: var(--theme); color: #fff; box-shadow: 0 0 0 4px color-mix(in srgb, var(--theme) 18%, transparent); }
	.steps .step.done .dot { background: #16a34a; color: #fff; }
	.steps .bar { flex: 1; height: 2px; background: #e2e8f0; border-radius: 2px; transition: background .2s; }
	.steps .bar.filled { background: #16a34a; }

	.section-hint { color: #64748b; font-size: 14px; margin-bottom: 14px; }

	.cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
	.cat-card { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; padding: 12px; border: 1.5px solid #e6e9f0; border-radius: 16px; background: #fff; cursor: pointer; text-align: left; transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease; }
	.cat-card:hover, .cat-card.active { transform: translateY(-3px); border-color: var(--theme); box-shadow: 0 14px 30px rgba(15,23,42,.10); }
	.cat-thumb { width: 100%; height: 84px; border-radius: 12px; background-size: cover; background-position: center; background-color: #eef1f6; }
	.cat-thumb.placeholder { display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: 800; color: var(--theme); background: color-mix(in srgb, var(--theme) 10%, #fff); }
	.cat-name { font-weight: 700; color: #0f172a; font-size: 15px; line-height: 1.25; }
	.cat-count { font-size: 12px; color: #94a3b8; }

	.subpanel { margin-top: 20px; padding: 18px; border: 1.5px solid color-mix(in srgb, var(--theme) 30%, #fff); border-radius: 16px; background: #fff; box-shadow: 0 14px 34px rgba(15,23,42,.07); animation: fade .18s ease; }
	@keyframes fade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
	.subpanel-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
	.subpanel-head .dot-sm { width: 10px; height: 10px; border-radius: 50%; background: var(--theme); }
	.subpanel-head strong { font-size: 16px; color: #0f172a; }
	.select-parent { margin-left: auto; border: 0; background: none; color: var(--theme); font-weight: 700; font-size: 13px; cursor: pointer; text-decoration: underline; }
	.sub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
	.sub-card { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 12px 14px; border: 1px solid #e6e9f0; border-radius: 12px; background: #fff; font-weight: 600; color: #1e293b; cursor: pointer; transition: all .12s ease; }
	.sub-card:hover { border-color: var(--theme); color: var(--theme); background: color-mix(in srgb, var(--theme) 6%, #fff); }

	.picked-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; margin-bottom: 20px; border-radius: 14px; background: #fff; border: 1.5px solid #e6e9f0; }
	.picked-bar .muted { display: block; font-size: 12px; color: #94a3b8; }
	.picked-bar strong { color: #0f172a; }

	.form-section { background: #fff; border: 1.5px solid #e6e9f0; border-radius: 16px; padding: 20px; }
	.section-title { font-weight: 800; color: #0f172a; margin-bottom: 14px; }
	.form-section .form-control, .form-section .form-select { border-radius: 12px; padding: 10px 14px; }

	.dropzone { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 30px; border: 2px dashed #cbd5e1; border-radius: 14px; background: #f8fafc; text-align: center; cursor: pointer; transition: all .15s ease; }
	.dropzone:hover, .dropzone.is-drag { border-color: var(--theme); background: color-mix(in srgb, var(--theme) 6%, #fff); }
	.dz-icon { font-size: 32px; color: var(--theme); }
	.dropzone .link { color: var(--theme); text-decoration: underline; font-weight: 700; }

	.thumbs { display: grid; grid-template-columns: repeat(auto-fill, minmax(104px, 1fr)); gap: 10px; margin-top: 14px; }
	.thumb { position: relative; aspect-ratio: 1; border-radius: 12px; overflow: hidden; border: 1px solid #e6e9f0; }
	.thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
	.thumb .remove { position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border: 0; border-radius: 50%; background: rgba(15,23,42,.72); color: #fff; font-size: 16px; line-height: 1; cursor: pointer; }
	.cover-tag { position: absolute; top: 4px; left: 4px; z-index: 1; padding: 2px 8px; border-radius: 999px; background: var(--theme); color: #fff; font-size: 11px; font-weight: 700; }

	.actions { display: flex; gap: 10px; }
	.actions .theme-btn { min-width: 200px; }

	.success-card { text-align: center; background: #fff; border: 1.5px solid #bbf7d0; border-radius: 18px; padding: 32px; }
	.success-icon { font-size: 54px; color: #16a34a; }
	.success-card h3 { margin: 12px 0 8px; font-weight: 850; color: #0f172a; }
	.copy-row { display: flex; gap: 8px; margin: 16px 0 8px; }
	.copy-row .form-control { font-size: 13px; }
	.warn { color: #b91c1c; font-size: 13px; margin: 0; }
</style>
