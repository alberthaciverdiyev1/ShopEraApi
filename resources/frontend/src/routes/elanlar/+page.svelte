<script lang="ts">
	import { onMount } from 'svelte';
	import { fetchShopProducts, type ShopMeta } from '$lib/services/shop';
	import { productImage, productTitle, productUrl, type ApiProduct } from '$lib/services/products';
	import { loadCategories, categories, categoryName, type Category } from '$lib/services/categories';
	import { loadDeliveryCities, deliveryCities } from '$lib/services/delivery';
	import { loadFilterTree, type ApiFilterNode } from '$lib/services/filters-tree';
	import ShopFilterTree from '$lib/components/shop/ShopFilterTree.svelte';

	let items = $state<ApiProduct[]>([]);
	let meta = $state<ShopMeta>({ current_page: 1, last_page: 1, per_page: 24, total: 0 });
	let loading = $state(true);
	let loadingMore = $state(false);
	let error = $state<string | null>(null);

	let search = $state('');
	let categoryId = $state<number | null>(null);
	let cityId = $state('');
	let condition = $state('');
	let priceMin = $state('');
	let priceMax = $state('');
	let sort = $state('newest');

	let filterTree = $state<ApiFilterNode[]>([]);
	let treeSel = $state<Record<number, number>>({});

	function flatCategories(list: Category[], depth = 0): Array<{ id: number; label: string }> {
		const out: Array<{ id: number; label: string }> = [];
		for (const c of list) {
			out.push({ id: c.id, label: `${'— '.repeat(depth)}${categoryName(c)}` });
			if (c.children?.length) out.push(...flatCategories(c.children, depth + 1));
		}
		return out;
	}
	const categoryOptions = $derived(flatCategories($categories));

	function options(filter: ApiFilterNode) {
		const dep = filter.depends_on_filter_id;
		if (dep) {
			const parent = treeSel[dep];
			return parent ? filter.values.filter((v) => Number(v.parent_value_id) === Number(parent)) : [];
		}
		return filter.values.filter((v) => !v.parent_value_id);
	}

	function onTreeChange(filter: ApiFilterNode, valueId: number | null) {
		const next = { ...treeSel };
		if (valueId) next[filter.id] = valueId;
		else delete next[filter.id];
		const reset = (id: number) => {
			for (const f of filterTree) {
				if (f.depends_on_filter_id === id) {
					delete next[f.id];
					reset(f.id);
				}
			}
		};
		reset(filter.id);
		treeSel = next;
		load();
	}

	async function loadTree() {
		treeSel = {};
		filterTree = categoryId ? await loadFilterTree(categoryId) : [];
	}

	async function load(targetPage = 1, append = false) {
		append ? (loadingMore = true) : (loading = true);
		error = null;
		try {
			const [order_by, order_type] = {
				newest: ['created_at', 'desc'],
				price_asc: ['price', 'asc'],
				price_desc: ['price', 'desc']
			}[sort] ?? ['created_at', 'desc'];

			const result = await fetchShopProducts({
				marketplace: true,
				search: search.trim() || undefined,
				category_ids: categoryId ? [categoryId] : undefined,
				city_id: cityId || undefined,
				condition: (condition as 'new' | 'used') || undefined,
				price_min: priceMin ? Number(priceMin) : undefined,
				price_max: priceMax ? Number(priceMax) : undefined,
				filter_value_ids: Object.values(treeSel).filter(Boolean),
				order_by: order_by as string,
				order_type: order_type as 'asc' | 'desc',
				page: targetPage,
				per_page: 24
			});

			items = append ? [...items, ...result.items] : result.items;
			meta = result.meta;
		} catch (e) {
			error = e instanceof Error ? e.message : 'Yüklənmədi.';
			if (!append) items = [];
		} finally {
			loading = false;
			loadingMore = false;
		}
	}

	function onCategoryChange() {
		loadTree();
		load();
	}

	onMount(() => {
		loadCategories();
		loadDeliveryCities();
		const c = Number(new URLSearchParams(window.location.search).get('category'));
		if (c) categoryId = c;
		loadTree();
		load();
	});
</script>

<svelte:head><title>Elanlar</title></svelte:head>

<section class="section-padding fix">
	<div class="container">
		<div class="d-flex align-items-center justify-content-between mb-3">
			<h2 class="mb-0">Elanlar <span class="text-muted fs-6">({meta.total})</span></h2>
			<a href="/elan/ver" class="theme-btn">Elan yerləşdir</a>
		</div>

		<form class="row g-2 mb-4" onsubmit={(e) => { e.preventDefault(); load(); }}>
			<div class="col-md-4">
				<input class="form-control" placeholder="Axtar…" bind:value={search} />
			</div>
			<div class="col-md-3">
				<select class="form-select" bind:value={categoryId} onchange={onCategoryChange}>
					<option value={null}>Bütün kateqoriyalar</option>
					{#each categoryOptions as c (c.id)}<option value={c.id}>{c.label}</option>{/each}
				</select>
			</div>
			<div class="col-md-2">
				<select class="form-select" bind:value={cityId}>
					<option value="">Bütün şəhərlər</option>
					{#each $deliveryCities as city (city.key)}<option value={city.key}>{city.name}</option>{/each}
				</select>
			</div>
			<div class="col-md-2">
				<select class="form-select" bind:value={condition}>
					<option value="">Vəziyyət</option>
					<option value="new">Yeni</option>
					<option value="used">İşlənmiş</option>
				</select>
			</div>
			<div class="col-md-1"><button class="theme-btn w-100" type="submit">Axtar</button></div>

			<div class="col-md-2"><input class="form-control" type="number" min="0" placeholder="Qiymət min" bind:value={priceMin} /></div>
			<div class="col-md-2"><input class="form-control" type="number" min="0" placeholder="Qiymət max" bind:value={priceMax} /></div>
			<div class="col-md-3">
				<select class="form-select" bind:value={sort} onchange={() => load()}>
					<option value="newest">Ən yeni</option>
					<option value="price_asc">Qiymət: əvvəl ucuz</option>
					<option value="price_desc">Qiymət: əvvəl baha</option>
				</select>
			</div>
		</form>

		{#if filterTree.length}
			<div class="mb-4 p-3 rounded" style="background:#f7f8fb;">
				<ShopFilterTree tree={filterTree} selection={treeSel} onchange={onTreeChange} />
			</div>
		{/if}

		{#if error}<div class="alert alert-danger">{error}</div>{/if}

		{#if loading}
			<p class="text-muted">Yüklənir…</p>
		{:else if items.length === 0}
			<p class="text-muted">Elan tapılmadı.</p>
		{:else}
			<div class="row g-4">
				{#each items as product (product.id)}
					<div class="col-6 col-md-4 col-lg-3">
						<a href={productUrl(product)} class="d-block h-100 text-decoration-none text-reset">
							<div class="card h-100 border-0 shadow-sm">
								<div style="position:relative;">
									<img src={productImage(product)} alt={productTitle(product)} class="card-img-top" style="height:200px;object-fit:cover;">
									{#if product.is_promoted}<span class="badge bg-warning text-dark" style="position:absolute;top:8px;left:8px;">İrəli çəkilmiş</span>{/if}
								</div>
								<div class="card-body">
									<p class="mb-1 fw-semibold">{productTitle(product)}</p>
									<p class="mb-0 text-primary fw-bold">{Number(product.discount || product.price || 0).toFixed(2)} ₼</p>
									{#if product.city}<p class="mb-0 text-muted small">{product.city}</p>{/if}
								</div>
							</div>
						</a>
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
	</div>
</section>
