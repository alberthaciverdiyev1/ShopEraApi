<script lang="ts">
	import { onMount } from 'svelte';
	import ShopProductCard from '$lib/components/cards/ShopProductCard.svelte';
	import DynamicFilter from '$lib/components/shop/DynamicFilter.svelte';
	import { fetchCategoryFilters, filterChoices, filterTitle, type FilterDefinition } from '$lib/services/filters';
	import { initPageBehaviors } from '$lib/theme/behaviors';
	import Select from '$lib/components/ui/Select.svelte';
	import SearchInput from '$lib/components/ui/SearchInput.svelte';
	import { categories, categoryName, loadCategories } from '$lib/services/categories';
	import { fetchShopFilters, fetchShopProducts, type ShopFacets, type ShopMeta } from '$lib/services/shop';
	import type { ApiProduct } from '$lib/services/products';
	import type { ApiPromoBlock } from '$lib/services/promoBlocks';
	import { locale, translate } from '$lib/i18n';

	let { promoBlocks = [] }: { promoBlocks?: ApiPromoBlock[] } = $props();

	const PRICE_MAX = 1000;
	const SORTS = [
		{ value: 'created_at:desc', label: 'Newest' },
		{ value: 'price:asc', label: 'Price: low to high' },
		{ value: 'price:desc', label: 'Price: high to low' },
		{ value: 'sales_count:desc', label: 'Best selling' }
	];

	let search = $state('');
	let categoryId = $state<number | null>(null);
	let expandedParentId = $state<number | null>(null);
	let brandId = $state<number | null>(null);
	let colorId = $state<number | null>(null);
	let sizeId = $state<number | null>(null);
	let priceMax = $state(PRICE_MAX);
	let sort = $state('created_at:desc');
	let page = $state(1);

	let items = $state<ApiProduct[]>([]);
	let meta = $state<ShopMeta>({ current_page: 1, last_page: 1, per_page: 24, total: 0 });
	let facets = $state<ShopFacets>({ brands: [], colors: [], sizes: [], price: { min: 0, max: 0 }, total: 0 });
	let priceCap = $state(PRICE_MAX);

	const priceMin = $derived(Math.floor(facets.price.min));
	const mobileAdBlock = $derived(promoBlocks.find((block) => block.type === 'ad') ?? promoBlocks.find((block) => block.type === 'offer') ?? null);
	const pricePercent = $derived.by(() => {
		const min = priceMin;
		const max = priceCap;
		if (max <= min) return 100;
		const current = Number(priceMax);
		const pct = ((current - min) / (max - min)) * 100;
		return Math.min(100, Math.max(0, Math.round(pct)));
	});

	/** Sidebar lists show 5 rows first; "Load more" reveals five more. */
	const PAGE_SIZE = 5;
	let visible = $state<Record<string, number>>({ categories: PAGE_SIZE, brands: PAGE_SIZE, colors: PAGE_SIZE, sizes: PAGE_SIZE });

	function shown<T>(key: string, list: T[]): T[] {
		return list.slice(0, visible[key] ?? PAGE_SIZE);
	}

	function showMore(key: string) {
		visible = { ...visible, [key]: (visible[key] ?? PAGE_SIZE) + PAGE_SIZE };
	}

	function resetLists() {
		visible = { categories: PAGE_SIZE, brands: PAGE_SIZE, colors: PAGE_SIZE, sizes: PAGE_SIZE };
	}

	let openFilters = $state<Record<string, boolean>>({
		categories: true,
		price: true
	});

	function toggleFilter(key: string) {
		openFilters = { ...openFilters, [key]: !openFilters[key] };
	}

	function isOpen(key: string): boolean {
		return Boolean(openFilters[key]);
	}

	let dynamicFilters = $state<FilterDefinition[]>([]);
	let selectedFilters = $state<Record<number, string[]>>({});
	let loading = $state(false);
	let loadingMore = $state(false);
	let error = $state<string | null>(null);
	let loadMoreEl = $state<HTMLDivElement | null>(null);
	let requestSerial = 0;

	/** Sidebar facets: brands / colours / sizes / price for the current category. */
	async function loadFacets() {
		try {
			const result = await fetchShopFilters(categoryId ? [categoryId] : []);
			facets = result;

			// Dynamic filters are defined per category, so they follow the selection.
			dynamicFilters = await fetchCategoryFilters(categoryId);
			selectedFilters = {};
			priceCap = result.price.max > 0 ? Math.ceil(result.price.max) : PRICE_MAX;
			priceMax = priceCap;
		} catch (error) {
			console.error('Failed to load filters', error);
		}
	}

	// The dynamic controls are created after the layout's first pass; this runs
	// once they are in the DOM and upgrades their <select> elements.
	$effect(() => {
		if (dynamicFilters.length) initPageBehaviors();
	});

	function setDynamicFilter(filterId: number, values: string[]) {
		selectedFilters = { ...selectedFilters, [filterId]: values };
		apply();
	}

	/** Choosing a category re-scopes the whole sidebar. */
	function selectCategory(id: number | null, parentId?: number | null) {
		const isSubcategoryClick = parentId !== undefined && parentId !== null;
		categoryId = id;
		// Track which parent is expanded for subcategory visibility
		if (id === null) {
			expandedParentId = null;
		} else if (isSubcategoryClick) {
			// Clicked a subcategory — keep parent expanded
			expandedParentId = parentId;
		} else {
			// Clicked a top-level parent
			expandedParentId = id;
		}

		if (!isSubcategoryClick) {
			// Only reset filters and reload facets for parent category changes
			brandId = null;
			colorId = null;
			sizeId = null;
			selectedFilters = {};
			openFilters = { categories: true, price: openFilters.price ?? true };
			resetLists();
			loadFacets();
		}

		apply();
	}

	async function load(targetPage = 1, append = false) {
		const requestId = ++requestSerial;
		if (append) {
			loadingMore = true;
		} else {
			loading = true;
		}
		error = null;

		try {
			const [order_by, order_type] = sort.split(':');
			const result = await fetchShopProducts({
				search: search.trim() || undefined,
				category_ids: categoryId ? [categoryId] : undefined,
				brand_ids: brandId ? [brandId] : undefined,
				color_ids: colorId ? [colorId] : undefined,
				size_ids: sizeId ? [sizeId] : undefined,
				price_max: priceCap > 0 && priceMax < priceCap ? priceMax : undefined,
				order_by,
				order_type: order_type as 'asc' | 'desc',
				page: targetPage,
				filters: Object.fromEntries(
					Object.entries(selectedFilters).filter(([, values]) => values.length)
				)
			});

			if (requestId !== requestSerial) return;

			page = result.meta.current_page;
			items = append
				? [...new Map([...items, ...result.items].map((product) => [product.id, product])).values()]
				: result.items;
			meta = result.meta;
		} catch (e) {
			if (requestId !== requestSerial) return;
			error = e instanceof Error ? e.message : 'Failed to load products';
			if (!append) items = [];
		} finally {
			if (requestId === requestSerial) {
				loading = false;
				loadingMore = false;
			}
		}
	}

	/** Any filter change restarts from page 1. */
	function apply() {
		page = 1;
		load(1);
	}

	function loadMore() {
		if (loading || loadingMore || meta.current_page >= meta.last_page) return;
		load(meta.current_page + 1, true);
	}

	let mobileFilterOpen = $state(false);

	const categoryNameSelected = $derived.by(() => {
		if (!categoryId) return null;
		for (const cat of $categories) {
			if (cat.id === categoryId) return categoryName(cat, $locale);
			const child = cat.children?.find((c) => c.id === categoryId);
			if (child) return categoryName(child, $locale);
		}
		return null;
	});

	const selectedParentCategory = $derived.by(() => {
		if (!expandedParentId) return null;
		return $categories.find((cat) => cat.id === expandedParentId) ?? null;
	});

	const brandNameSelected = $derived(
		brandId ? facets.brands.find((b) => b.id === brandId)?.name ?? null : null
	);

	const colorNameSelected = $derived(
		colorId ? facets.colors.find((c) => c.id === colorId)?.name ?? null : null
	);

	const sizeNameSelected = $derived(
		sizeId ? facets.sizes.find((s) => s.id === sizeId)?.name ?? null : null
	);

	const activeFilterCount = $derived.by(() => {
		let count = 0;
		if (categoryId) count++;
		if (brandId) count++;
		if (colorId) count++;
		if (sizeId) count++;
		if (priceCap > 0 && priceMax < priceCap) count++;
		if (search.trim()) count++;
		count += Object.values(selectedFilters).filter((arr) => arr.length > 0).length;
		return count;
	});

	function clearAllFilters() {
		categoryId = null;
		expandedParentId = null;
		brandId = null;
		colorId = null;
		sizeId = null;
		search = '';
		priceMax = priceCap;
		selectedFilters = {};
		loadFacets();
		apply();
	}

	function compactLabel(value: string, fallback = $translate('Categories')) {
		const clean = value.trim() || fallback;
		return clean.length > 16 ? `${clean.slice(0, 13)}...` : clean;
	}

	$effect(() => {
		if (typeof document !== 'undefined') {
			document.body.style.overflow = mobileFilterOpen ? 'hidden' : '';
			document.body.classList.toggle('shop-mobile-filter-open', mobileFilterOpen);
		}
		return () => {
			if (typeof document !== 'undefined') {
				document.body.style.overflow = '';
				document.body.classList.remove('shop-mobile-filter-open');
			}
		};
	});

	$effect(() => {
		if (!loadMoreEl || typeof IntersectionObserver === 'undefined') return;

		const observer = new IntersectionObserver(
			(entries) => {
				if (entries.some((entry) => entry.isIntersecting)) {
					loadMore();
				}
			},
			{ rootMargin: '420px 0px 560px' }
		);

		observer.observe(loadMoreEl);
		return () => observer.disconnect();
	});

	onMount(() => {
		document.body.classList.add('shop-mobile-page');

		(async () => {
			// Deep link: /shop?category=6 preselects a category.
			const fromUrl = Number(new URLSearchParams(window.location.search).get('category'));
			if (fromUrl) {
				categoryId = fromUrl;
				expandedParentId = fromUrl;
			}

			await loadCategories();

			// If the deep-linked category is a child, find the parent to expand it
			if (fromUrl && $categories.length > 0) {
				const parent = $categories.find((c) => c.children?.some((ch) => ch.id === fromUrl));
				if (parent) {
					expandedParentId = parent.id;
				}
			}

			loadFacets();
			await load(1);
		})();

		return () => {
			document.body.classList.remove('shop-mobile-page');
		};
	});
</script>

{#snippet sidebarWidgets()}
	<div class="main-sidebar-1">
		<div class="single-sidebar-widget">
			<SearchInput bind:value={search} placeholder={$translate('Search products...')} onSubmit={() => apply()} />
		</div>

		<div class="single-sidebar-widget">
			<button
				type="button"
				class="sidebar-widget-header"
				onclick={() => toggleFilter('price')}
				aria-expanded={isOpen('price')}
			>
				<h3 class="single-sidebar-widget__wid-title--title">{$translate('Price range')}</h3>
				<i class="fa-solid fa-chevron-down widget-collapse-icon" class:rotated={isOpen('price')}></i>
			</button>
			{#if isOpen('price')}
				<div class="sidebar-widget-body">
					<div class="single-sidebar-widget__filter-price-widget-categories">
						<div class="range-slider">
							<div class="price-filter-summary">
								<div>
									<span>{$translate('Selected range')}</span>
									<strong>${priceMin} - ${priceMax}</strong>
								</div>
								{#if priceMax < priceCap}
									<button type="button" onclick={() => { priceMax = priceCap; apply(); }}>{$translate('Reset')}</button>
								{/if}
							</div>
							<input
								type="range"
								class="form-range"
								min={priceMin}
								max={priceCap}
								step="1"
								bind:value={priceMax}
								onchange={apply}
								style="--slider-pct: {pricePercent}%;"
								aria-label={$translate('Price filter')}
							/>
						</div>
					</div>
				</div>
			{/if}
		</div>

		{#if $categories.length > 0}
			<div class="single-sidebar-widget">
				<button
					type="button"
					class="sidebar-widget-header"
					onclick={() => toggleFilter('categories')}
					aria-expanded={isOpen('categories')}
				>
					<h3 class="single-sidebar-widget__wid-title--title">{$translate('Categories')}</h3>
					{#if categoryId}
						<span class="active-filter-badge">1</span>
					{/if}
					<i class="fa-solid fa-chevron-down widget-collapse-icon" class:rotated={isOpen('categories')}></i>
				</button>
				{#if isOpen('categories')}
					<div class="sidebar-widget-body">
						<div class="single-sidebar-widget__shop-widget-categories">
							<ul>
								<li>
									<a href="#!" onclick={(e) => { e.preventDefault(); selectCategory(null); }}>
										<i class="fa-solid fa-chevron-right"></i>{$translate('All Categories')}</a>
								</li>
								{#each shown('categories', $categories) as category (category.id)}
									<li class="parent-category-item">
										<a href="#!"
										   class="parent-category-link"
										   class:active={categoryId === category.id || expandedParentId === category.id}
										   onclick={(e) => { e.preventDefault(); selectCategory(categoryId === category.id ? null : category.id); }}>
											<i class="fa-solid fa-chevron-right"></i>{categoryName(category, $locale)}</a>
									</li>
									{#if expandedParentId === category.id && category.children && category.children.length > 0}
										{#each category.children as child (child.id)}
											<li class="subcategory-item">
												<a href="#!"
												   class="subcategory-link"
												   class:active={categoryId === child.id}
												   onclick={(e) => { e.preventDefault(); selectCategory(categoryId === child.id ? category.id : child.id, category.id); }}>
													<i class="fa-solid fa-turn-up"></i>{categoryName(child, $locale)}</a>
											</li>
										{/each}
									{/if}
								{/each}
								{#if $categories.length > visible.categories}
									<li class="sidebar-load-more-item">
										<a href="#!" class="sidebar-load-more" onclick={(e) => { e.preventDefault(); showMore('categories'); }}>
											<i class="fa-solid fa-chevron-down"></i>{$translate('More')}
										</a>
									</li>
								{/if}
							</ul>
						</div>
					</div>
				{/if}
			</div>
		{/if}

		{#if facets.brands.length}
			<div class="single-sidebar-widget">
				<button
					type="button"
					class="sidebar-widget-header"
					onclick={() => toggleFilter('brands')}
					aria-expanded={isOpen('brands')}
				>
					<h3 class="single-sidebar-widget__wid-title--title">{$translate('Brands')}</h3>
					{#if brandId}
						<span class="active-filter-badge">1</span>
					{/if}
					<i class="fa-solid fa-chevron-down widget-collapse-icon" class:rotated={isOpen('brands')}></i>
				</button>
				{#if isOpen('brands')}
					<div class="sidebar-widget-body">
						<div class="single-sidebar-widget__shop-widget-categories">
							<ul>
								{#each shown('brands', facets.brands) as brand (brand.id)}
									<li>
										<a href="#!"
										   class:active={brandId === brand.id}
										   onclick={(e) => { e.preventDefault(); brandId = brandId === brand.id ? null : brand.id; apply(); }}>
											<span class="text">
												{#if brand.image}
													<img class="brand-thumb" src={brand.image} alt={brand.name} loading="lazy">
												{/if}
												{brand.name}
											</span>
											<span>{brand.products_count ?? 0}</span>
										</a>
									</li>
								{/each}
								{#if facets.brands.length > visible.brands}
									<li class="sidebar-load-more-item">
										<a href="#!" class="sidebar-load-more" onclick={(e) => { e.preventDefault(); showMore('brands'); }}>
											<i class="fa-solid fa-chevron-down"></i>{$translate('More')}
										</a>
									</li>
								{/if}
							</ul>
						</div>
					</div>
				{/if}
			</div>
		{/if}

		{#if facets.colors.length}
			<div class="single-sidebar-widget">
				<button
					type="button"
					class="sidebar-widget-header"
					onclick={() => toggleFilter('colors')}
					aria-expanded={isOpen('colors')}
				>
					<h3 class="single-sidebar-widget__wid-title--title">{$translate('Colors')}</h3>
					{#if colorId}
						<span class="active-filter-badge">1</span>
					{/if}
					<i class="fa-solid fa-chevron-down widget-collapse-icon" class:rotated={isOpen('colors')}></i>
				</button>
				{#if isOpen('colors')}
					<div class="sidebar-widget-body">
						<div class="single-sidebar-widget__widget-categories">
							<ul>
								{#each shown('colors', facets.colors) as color (color.id)}
									<li>
										<a href="#!"
										   class:active={colorId === color.id}
										   onclick={(e) => { e.preventDefault(); colorId = colorId === color.id ? null : color.id; apply(); }}>
											<span class="text">
												<svg width="18" height="18" viewBox="0 0 18 18" fill="none"
													 xmlns="http://www.w3.org/2000/svg">
													<circle cx="9" cy="9" r="9" fill={color.hex ?? '#cccccc'}></circle>
												</svg>
												{color.name ?? '—'}
											</span>
											<span>{color.products_count ?? 0}</span>
										</a>
									</li>
								{/each}
								{#if facets.colors.length > visible.colors}
									<li class="sidebar-load-more-item">
										<a href="#!" class="sidebar-load-more" onclick={(e) => { e.preventDefault(); showMore('colors'); }}>
											<i class="fa-solid fa-chevron-down"></i>{$translate('More')}
										</a>
									</li>
								{/if}
							</ul>
						</div>
					</div>
				{/if}
			</div>
		{/if}

		{#each dynamicFilters as filter (filter.id)}
			<DynamicFilter
				{filter}
				selected={selectedFilters[filter.id] ?? []}
				open={isOpen(`filter_${filter.id}`)}
				ontoggle={() => toggleFilter(`filter_${filter.id}`)}
				onchange={(values) => setDynamicFilter(filter.id, values)}
			/>
		{/each}

		{#if facets.sizes.length}
			<div class="single-sidebar-widget">
				<button
					type="button"
					class="sidebar-widget-header"
					onclick={() => toggleFilter('sizes')}
					aria-expanded={isOpen('sizes')}
				>
					<h3 class="single-sidebar-widget__wid-title--title">{$translate('Sizes')}</h3>
					{#if sizeId}
						<span class="active-filter-badge">1</span>
					{/if}
					<i class="fa-solid fa-chevron-down widget-collapse-icon" class:rotated={isOpen('sizes')}></i>
				</button>
				{#if isOpen('sizes')}
					<div class="sidebar-widget-body">
						<div class="single-sidebar-widget__widget-categories">
							<ul>
								{#each shown('sizes', facets.sizes) as size (size.id)}
									<li>
										<a href="#!"
										   class:active={sizeId === size.id}
										   onclick={(e) => { e.preventDefault(); sizeId = sizeId === size.id ? null : size.id; apply(); }}>
											<span class="text">{size.name ?? '—'}</span>
											<span>{size.products_count ?? 0}</span>
										</a>
									</li>
								{/each}
								{#if facets.sizes.length > visible.sizes}
									<li class="sidebar-load-more-item">
										<a href="#!" class="sidebar-load-more" onclick={(e) => { e.preventDefault(); showMore('sizes'); }}>
											<i class="fa-solid fa-chevron-down"></i>{$translate('More')}
										</a>
									</li>
								{/if}
							</ul>
						</div>
					</div>
				{/if}
			</div>
		{/if}
	</div>
{/snippet}

<!-- Shop Section -->
<section class="shop-section section-padding2 shop-mobile-app">
	<div class="container">
		<div class="mobile-market-header d-lg-none">
			{#if mobileAdBlock}
				<a class="mobile-ad-banner" href={mobileAdBlock.url || '/shop'}>
					{#if mobileAdBlock.image}
						<img src={mobileAdBlock.image} alt={mobileAdBlock.title || 'Reklam'} loading="lazy" />
					{/if}
					<span class="mobile-ad-content">
						{#if mobileAdBlock.badge}<small>{mobileAdBlock.badge}</small>{/if}
						{#if mobileAdBlock.title}<strong>{mobileAdBlock.title}</strong>{/if}
						{#if mobileAdBlock.subtitle}<em>{mobileAdBlock.subtitle}</em>{/if}
					</span>
				</a>
			{/if}

			<div class="mobile-sticky-filters">
				<div class="mobile-category-heading">
					<strong>{categoryNameSelected ?? $translate('All products')}</strong>
					<span>{$translate('{count} products', { count: meta.total.toLocaleString($locale === 'az' ? 'az-AZ' : $locale) })}</span>
				</div>

				<div class="mobile-category-strip" aria-label={$translate('Categories')}>
					{#each $categories as category (category.id)}
						<button
							type="button"
							class:active={categoryId === category.id || expandedParentId === category.id}
							class="mobile-category-card"
							onclick={() => selectCategory(categoryId === category.id ? null : category.id)}
						>
							<span class="mobile-category-thumb">
								{#if category.image}
									<img src={category.image} alt={categoryName(category, $locale)} loading="lazy" />
								{:else}
									<i class="fa-regular fa-grid-2"></i>
								{/if}
							</span>
							<small>{compactLabel(categoryName(category, $locale))}</small>
						</button>
					{/each}
				</div>

				{#if selectedParentCategory?.children?.length}
					<div class="mobile-subcategory-strip" aria-label={$translate('Subcategory')}>
						<button
							type="button"
							class="mobile-subcategory-chip"
							class:active={categoryId === selectedParentCategory.id}
							onclick={() => selectCategory(selectedParentCategory.id)}
						>
							{$translate('All')}
						</button>
						{#each selectedParentCategory.children as child (child.id)}
							<button
								type="button"
								class="mobile-subcategory-chip"
								class:active={categoryId === child.id}
								onclick={() => selectCategory(categoryId === child.id ? selectedParentCategory.id : child.id, selectedParentCategory.id)}
							>
								{compactLabel(categoryName(child, $locale), $translate('Subcategory'))}
							</button>
						{/each}
					</div>
				{/if}

				<div class="mobile-filter-chips" aria-label={$translate('Quick filters')}>
					<button
						type="button"
						class="mobile-chip"
						onclick={() => mobileFilterOpen = true}
						aria-label={$translate('Filters')}
					>
						<i class="fa-solid fa-sliders"></i>
						<span>{$translate('Filters')}</span>
						{#if activeFilterCount > 0}
							<span class="filter-count-badge">{activeFilterCount}</span>
						{/if}
					</button>
					<label class="mobile-filter-select mobile-sort-select" aria-label={$translate('Sort')}>
						<i class="fa-solid fa-arrow-down-wide-short" aria-hidden="true"></i>
						<select value={sort} onchange={(event) => { sort = event.currentTarget.value; apply(); }}>
							{#each SORTS as option (option.value)}
								<option value={option.value}>{$translate(option.label)}</option>
							{/each}
						</select>
					</label>
					{#if facets.brands.length}
						<label class="mobile-filter-select">
							<span>{$translate('Brand')}</span>
							<select value={brandId?.toString() ?? ''} onchange={(event) => { brandId = event.currentTarget.value ? Number(event.currentTarget.value) : null; apply(); }}>
								<option value="">{$translate('All')}</option>
								{#each facets.brands as brand (brand.id)}
									<option value={brand.id}>{brand.name ?? $translate('Brand')}</option>
								{/each}
							</select>
						</label>
					{/if}
					{#if facets.colors.length}
						<label class="mobile-filter-select">
							<span>{$translate('Color')}</span>
							<select value={colorId?.toString() ?? ''} onchange={(event) => { colorId = event.currentTarget.value ? Number(event.currentTarget.value) : null; apply(); }}>
								<option value="">{$translate('All')}</option>
								{#each facets.colors as color (color.id)}
									<option value={color.id}>{color.name ?? $translate('Color')}</option>
								{/each}
							</select>
						</label>
					{/if}
					{#if facets.sizes.length}
						<label class="mobile-filter-select">
							<span>{$translate('Size')}</span>
							<select value={sizeId?.toString() ?? ''} onchange={(event) => { sizeId = event.currentTarget.value ? Number(event.currentTarget.value) : null; apply(); }}>
								<option value="">{$translate('All')}</option>
								{#each facets.sizes as size (size.id)}
									<option value={size.id}>{size.name ?? $translate('Size')}</option>
								{/each}
							</select>
						</label>
					{/if}
					{#each dynamicFilters as filter (filter.id)}
						{@const choices = filterChoices(filter)}
						{#if choices.length}
							<label class="mobile-filter-select">
								<span>{compactLabel(filterTitle(filter, $locale), $translate('Filters'))}</span>
								<select
									value={(selectedFilters[filter.id] ?? [])[0] ?? ''}
									onchange={(event) => setDynamicFilter(filter.id, event.currentTarget.value ? [event.currentTarget.value] : [])}
								>
									<option value="">{$translate('All')}</option>
									{#each choices as choice (choice)}
										<option value={choice}>{choice}</option>
									{/each}
								</select>
							</label>
						{/if}
					{/each}
				</div>
			</div>

			<!-- Active Filter Badges Row -->
			{#if activeFilterCount > 0}
				<div class="active-filter-chips mt-2">
					{#if categoryNameSelected}
						<button type="button" class="filter-chip" onclick={() => selectCategory(null)}>
							<span>{categoryNameSelected}</span>
							<i class="fa-solid fa-xmark"></i>
						</button>
					{/if}
					{#if brandNameSelected}
						<button type="button" class="filter-chip" onclick={() => { brandId = null; apply(); }}>
							<span>{brandNameSelected}</span>
							<i class="fa-solid fa-xmark"></i>
						</button>
					{/if}
					{#if colorNameSelected}
						<button type="button" class="filter-chip" onclick={() => { colorId = null; apply(); }}>
							<span>{colorNameSelected}</span>
							<i class="fa-solid fa-xmark"></i>
						</button>
					{/if}
					{#if sizeNameSelected}
						<button type="button" class="filter-chip" onclick={() => { sizeId = null; apply(); }}>
							<span>{sizeNameSelected}</span>
							<i class="fa-solid fa-xmark"></i>
						</button>
					{/if}
					{#if priceCap > 0 && priceMax < priceCap}
						<button type="button" class="filter-chip" onclick={() => { priceMax = priceCap; apply(); }}>
							<span>${priceMax}</span>
							<i class="fa-solid fa-xmark"></i>
						</button>
					{/if}
					{#if search.trim()}
						<button type="button" class="filter-chip" onclick={() => { search = ''; apply(); }}>
							<span>"{search}"</span>
							<i class="fa-solid fa-xmark"></i>
						</button>
					{/if}
					<button type="button" class="clear-all-chip" onclick={clearAllFilters}>
						{$translate('Clear all')}
					</button>
				</div>
			{/if}
		</div>

		<!-- Desktop Sort & Counter Bar (Full width above sidebar and products, so sidebar & products start aligned) -->
		<div class="desktop-shop-toolbar mb-4 d-none d-lg-flex justify-content-between align-items-center">
			<div class="desktop-shop-toolbar__count">
				<p class="mb-0 text-muted fw-semibold">
					{#if loading}{$translate('Loading')}…
					{:else}{$translate('{shown} / {total} products shown', { shown: items.length, total: meta.total })}{/if}
				</p>
			</div>
			<div class="desktop-shop-toolbar__sort d-flex align-items-center gap-2">
				<span class="text-muted small">{$translate('Sort')}:</span>
				<Select value={sort} options={SORTS.map((option) => ({ value: option.value, label: $translate(option.label) }))}
						onchange={(value) => { sort = value; apply(); }} />
			</div>
		</div>

		<div class="row gx-30">
			<!-- Desktop Sidebar (Hidden on tablet/mobile < 992px) -->
			<div class="col-lg-3 d-none d-lg-block">
				{@render sidebarWidgets()}
			</div>

			<!-- Products Grid Container -->
			<div class="col-lg-9">

				{#if error}
					<p class="text-center py-5">{error}</p>
				{:else if loading && items.length === 0}
					<div class="shop-loader-grid" aria-label={$translate('Loading products')}>
						{#each Array(8) as _}
							<div class="shop-loader-card">
								<span class="loader-shimmer loader-image"></span>
								<span class="loader-shimmer loader-line short"></span>
								<span class="loader-shimmer loader-line"></span>
								<span class="loader-shimmer loader-price"></span>
							</div>
						{/each}
					</div>
				{:else if !loading && items.length === 0}
					<div class="text-center py-5">
						<i class="fa-light fa-box-open fs-1 text-muted mb-3 d-block"></i>
						<h5>{$translate('No matching products found')}</h5>
						<p class="text-muted small">{$translate('Try changing or clearing the filters.')}</p>
						<button type="button" class="theme-btn btn-sm mt-2" onclick={clearAllFilters}>{$translate('Reset filters')}</button>
					</div>
				{:else}
					<!-- Responsive product grid: mobile 2, md 3, large 4 -->
					<div class="row g-2 g-md-3">
						{#each items as product (product.id)}
							<div class="col-6 col-md-4 col-xl-3">
								<ShopProductCard {product} />
							</div>
						{/each}
					</div>
				{/if}

				{#if !error && items.length > 0}
					<div bind:this={loadMoreEl} class="infinite-scroll-sentinel" aria-live="polite">
						{#if loadingMore}
							<span class="infinite-loader">
								<span class="infinite-dots" aria-hidden="true"><i></i><i></i><i></i></span>
								<span>{$translate('Loading more products')}</span>
							</span>
						{:else if meta.current_page < meta.last_page}
							<span class="infinite-hint">{$translate('Scroll down to load more products')}</span>
						{:else}
							<span class="infinite-end">{$translate('All products are shown')}</span>
						{/if}
					</div>
				{/if}
			</div>
		</div>
	</div>
</section>

<!-- Mobile Filter Drawer (Slide-in panel) -->
{#if mobileFilterOpen}
	<div
		class="mobile-drawer-backdrop"
		onclick={() => mobileFilterOpen = false}
		role="button"
		tabindex="0"
		onkeydown={(e) => e.key === 'Escape' && (mobileFilterOpen = false)}
		aria-label={$translate('Close')}
	></div>
	<aside class="mobile-drawer-panel" aria-label={$translate('Filters')}>
		<div class="mobile-drawer-header">
			<div class="d-flex align-items-center gap-3">
				<button
					type="button"
					class="mobile-drawer-close-btn"
					onclick={() => mobileFilterOpen = false}
					aria-label={$translate('Back')}
				>
					<i class="fa-solid fa-arrow-left"></i>
				</button>
				<div class="d-flex align-items-center gap-2">
					<h5 class="mb-0 fw-bold">{$translate('Filters')}</h5>
					{#if activeFilterCount > 0}
						<span class="badge bg-danger rounded-pill">{activeFilterCount}</span>
					{/if}
				</div>
			</div>
			<button
				type="button"
				class="mobile-drawer-reset-header-btn"
				onclick={clearAllFilters}
			>
				{$translate('Reset')}
			</button>
		</div>

		<div class="mobile-drawer-body">
			{@render sidebarWidgets()}
		</div>

		<div class="mobile-drawer-footer">
			<button
				type="button"
				class="btn-drawer-apply w-100"
				onclick={() => mobileFilterOpen = false}
			>
				{$translate('Show products ({count})', { count: meta.total })}
			</button>
		</div>
	</aside>
{/if}

<style>
	/* Small round artwork in front of the brand name (mirrors the colour swatch). */
	.brand-thumb {
		width: 20px;
		height: 20px;
		border-radius: 50%;
		object-fit: cover;
		display: inline-block;
		margin-right: 8px;
		vertical-align: middle;
		background: #f2f2f2;
	}

	.shop-section {
		overflow: visible !important;
	}

	.shop-section :global(.col-lg-3) {
		align-self: stretch !important;
		overflow: visible !important;
	}

	.main-sidebar-1 {
		position: -webkit-sticky;
		position: sticky;
		top: 96px;
		max-height: calc(100vh - 116px);
		overflow-y: auto;
		overflow-x: hidden;
		border: 1px solid #e5eaf1;
		border-radius: 14px;
		background: #fff;
		box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
		scrollbar-width: thin;
		scrollbar-color: #cbd5e1 transparent;
		z-index: 10;
	}

	.main-sidebar-1::-webkit-scrollbar {
		width: 4px;
	}

	.main-sidebar-1::-webkit-scrollbar-track {
		background: transparent;
	}

	.main-sidebar-1::-webkit-scrollbar-thumb {
		background: #cbd5e1;
		border-radius: 4px;
	}

	@media (min-width: 992px) {
		.main-sidebar-1 {
			margin-top: 0 !important;
		}
	}

	.desktop-shop-toolbar {
		padding: 12px 20px;
		background: #ffffff;
		border: 1px solid #e5eaf1;
		border-radius: 12px;
		box-shadow: 0 4px 18px rgba(15, 23, 42, 0.03);
	}

	.main-sidebar-1 :global(.single-sidebar-widget),
	.main-sidebar-1 .single-sidebar-widget {
		margin: 0 !important;
		padding: 18px 18px !important;
		border: 0 !important;
		border-radius: 0 !important;
		background: #fff !important;
		box-shadow: none !important;
	}

	.main-sidebar-1 :global(.single-sidebar-widget + .single-sidebar-widget),
	.main-sidebar-1 .single-sidebar-widget + .single-sidebar-widget {
		border-top: 1px solid #eef2f6 !important;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__search-widget form) {
		position: relative;
		display: flex;
		align-items: center;
		height: 44px;
		border: 1px solid #dfe5ee;
		border-radius: 10px;
		background: #f8fafc;
		transition:
			border-color 0.16s ease,
			background-color 0.16s ease,
			box-shadow 0.16s ease;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__search-widget form:focus-within) {
		border-color: color-mix(in srgb, var(--theme) 38%, #dfe5ee);
		background: #fff;
		box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme) 10%, transparent);
	}

	.main-sidebar-1 :global(.single-sidebar-widget__search-widget input) {
		min-width: 0;
		flex: 1;
		height: 100%;
		padding: 0 12px !important;
		border: 0 !important;
		background: transparent !important;
		color: #111827;
		font-size: 13.5px;
		outline: 0;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__search-widget input::placeholder) {
		color: #8a94a6;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__search-widget button) {
		flex: 0 0 38px;
		width: 38px;
		height: 38px;
		margin-right: 3px;
		border: 0;
		border-radius: 9px;
		background: transparent;
		color: var(--theme);
		font-size: 15px;
	}

	.infinite-scroll-sentinel {
		min-height: 74px;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 10px;
		padding: 24px 12px 8px;
		color: #64748b;
		font-size: 14px;
		font-weight: 700;
		text-align: center;
	}

	.shop-loader-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 10px;
	}

	.shop-loader-card {
		overflow: hidden;
		padding: 8px;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 12px;
		background: #fff;
		box-shadow: 0 10px 26px rgba(15, 23, 42, 0.04);
	}

	.loader-shimmer {
		position: relative;
		display: block;
		overflow: hidden;
		border-radius: 999px;
		background: #e9edf3;
	}

	.loader-shimmer::after {
		content: '';
		position: absolute;
		inset: 0;
		transform: translateX(-100%);
		background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.78), transparent);
		animation: shimmerSweep 1.15s ease-in-out infinite;
	}

	.loader-image {
		height: 210px;
		margin-bottom: 10px;
		border-radius: 10px;
		background: linear-gradient(135deg, #e9f6ef, #eef2f7);
	}

	.loader-line {
		width: 82%;
		height: 10px;
		margin-bottom: 8px;
	}

	.loader-line.short {
		width: 46%;
	}

	.loader-price {
		width: 58%;
		height: 13px;
		background: color-mix(in srgb, var(--theme) 14%, #e9edf3);
	}

	.infinite-hint,
	.infinite-end {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 34px;
		padding: 0 16px;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 999px;
		background: #fff;
	}

	.infinite-end {
		color: var(--theme);
		background: color-mix(in srgb, var(--theme) 8%, #fff);
		border-color: color-mix(in srgb, var(--theme) 18%, transparent);
	}

	.infinite-loader {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 10px;
		min-height: 40px;
		padding: 0 18px;
		border-radius: 999px;
		color: var(--theme);
		background: color-mix(in srgb, var(--theme) 9%, #fff);
		border: 1px solid color-mix(in srgb, var(--theme) 18%, transparent);
		box-shadow: 0 12px 30px color-mix(in srgb, var(--theme) 12%, transparent);
	}

	.infinite-dots {
		display: inline-flex;
		align-items: center;
		gap: 4px;
	}

	.infinite-dots i {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: currentColor;
		animation: dotPulse 0.95s ease-in-out infinite;
	}

	.infinite-dots i:nth-child(2) {
		animation-delay: 0.14s;
	}

	.infinite-dots i:nth-child(3) {
		animation-delay: 0.28s;
	}

	@keyframes shimmerSweep {
		100% {
			transform: translateX(100%);
		}
	}

	@keyframes dotPulse {
		0%,
		80%,
		100% {
			opacity: 0.35;
			transform: translateY(0) scale(0.9);
		}
		40% {
			opacity: 1;
			transform: translateY(-2px) scale(1);
		}
	}

	.range-slider {
		display: grid;
		gap: 16px;
		padding: 2px 0 4px;
	}

	.price-filter-summary {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 12px;
		border: 1px solid #e7edf4;
		border-radius: 12px;
		background: #f8fafc;
	}

	.price-filter-summary span {
		display: block;
		margin-bottom: 6px;
		color: #64748b;
		font-size: 10.5px;
		font-weight: 800;
		line-height: 1;
		text-transform: uppercase;
	}

	.price-filter-summary strong {
		display: block;
		color: #0f172a;
		font-size: 18px;
		font-weight: 800;
		line-height: 1.1;
	}

	.price-filter-summary button {
		flex: 0 0 auto;
		height: 32px;
		padding: 0 12px;
		border: 1px solid #dce5ef;
		border-radius: 9px;
		background: #ffffff;
		color: var(--theme);
		font-size: 12px;
		font-weight: 800;
		cursor: pointer;
		transition: border-color 0.16s ease, background-color 0.16s ease;
	}

	.price-filter-summary button:hover {
		border-color: color-mix(in srgb, var(--theme) 35%, #dce5ef);
		background: color-mix(in srgb, var(--theme) 6%, #fff);
	}

	.range-slider .form-range {
		width: 100%;
		height: 22px;
		padding: 8px 0;
		border: 0;
		border-radius: 999px;
		background: transparent !important;
		outline: none;
		-webkit-appearance: none;
		appearance: none;
		cursor: pointer;
		box-shadow: none;
	}

	.range-slider .form-range:focus-visible {
		box-shadow: 0 0 0 4px color-mix(in srgb, var(--theme) 12%, transparent);
	}

	.range-slider .form-range::-webkit-slider-runnable-track {
		height: 6px;
		border-radius: 999px;
		background:
			linear-gradient(
				90deg,
				var(--theme) 0 var(--slider-pct),
				#e5eaf1 var(--slider-pct) 100%
			) !important;
	}

	.range-slider .form-range::-moz-range-track {
		height: 6px;
		border-radius: 999px;
		background:
			linear-gradient(
				90deg,
				var(--theme) 0 var(--slider-pct),
				#e5eaf1 var(--slider-pct) 100%
			) !important;
	}

	.range-slider .form-range::-webkit-slider-thumb {
		-webkit-appearance: none;
		appearance: none;
		width: 18px;
		height: 18px;
		margin-top: -6px;
		border: 4px solid #ffffff;
		border-radius: 50%;
		background: var(--theme);
		box-shadow:
			0 0 0 1px rgba(15, 23, 42, 0.08),
			0 7px 16px color-mix(in srgb, var(--theme) 24%, transparent);
		cursor: pointer;
		transition:
			transform 0.14s ease,
			box-shadow 0.14s ease;
	}

	.range-slider .form-range::-webkit-slider-thumb:hover,
	.range-slider .form-range::-webkit-slider-thumb:active {
		transform: scale(1.08);
		box-shadow:
			0 0 0 7px color-mix(in srgb, var(--theme) 16%, transparent),
			0 8px 20px color-mix(in srgb, var(--theme) 30%, transparent);
	}

	.range-slider .form-range::-moz-range-thumb {
		width: 18px;
		height: 18px;
		border: 4px solid #ffffff;
		border-radius: 50%;
		background: var(--theme);
		box-shadow:
			0 0 0 1px rgba(15, 23, 42, 0.08),
			0 7px 16px color-mix(in srgb, var(--theme) 24%, transparent);
		cursor: pointer;
	}

	:global(.main-sidebar-1 .single-sidebar-widget ul li.sidebar-load-more-item) {
		display: flex !important;
		align-items: center !important;
		justify-content: flex-start !important;
		padding: 8px 0px !important;
		width: 100% !important;
	}

	:global(.main-sidebar-1 .single-sidebar-widget ul li.sidebar-load-more-item a.sidebar-load-more) {
		display: inline-flex !important;
		align-items: center !important;
		justify-content: flex-start !important;
		width: auto !important;
		color: #64748b !important;
		font-family: "Albert Sans", sans-serif !important;
		font-size: 13px !important;
		font-style: normal !important;
		font-weight: 700 !important;
		line-height: 1.2 !important;
		text-transform: capitalize !important;
		transition: all 0.3s ease-in-out !important;
		text-decoration: none !important;
		cursor: pointer !important;
		padding: 8px !important;
		border-radius: 9px;
	}

	:global(.main-sidebar-1 .single-sidebar-widget ul li.sidebar-load-more-item a.sidebar-load-more i) {
		margin-right: 8px !important;
		font-size: 11px !important;
	}

	:global(.main-sidebar-1 .single-sidebar-widget ul li.sidebar-load-more-item:hover a.sidebar-load-more),
	:global(.main-sidebar-1 .single-sidebar-widget ul li.sidebar-load-more-item a.sidebar-load-more:hover) {
		background: #f7f9fb;
		color: var(--theme) !important;
	}

	.sidebar-widget-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		background: transparent;
		border: none;
		padding: 0;
		margin: 0;
		cursor: pointer;
		text-align: left;
	}

	.sidebar-widget-header .single-sidebar-widget__wid-title--title {
		margin-bottom: 0;
		color: #111827;
		font-size: 14px !important;
		font-weight: 750 !important;
		line-height: 1.3 !important;
	}

	.sidebar-widget-body {
		margin-top: 14px;
	}

	.widget-collapse-icon {
		font-size: 11px;
		color: #8b95a5;
		transition: transform 0.25s ease, color 0.25s ease;
	}

	.sidebar-widget-header:hover .widget-collapse-icon,
	.sidebar-widget-header:hover .single-sidebar-widget__wid-title--title {
		color: var(--theme, #ed0006);
	}

	.widget-collapse-icon.rotated {
		transform: rotate(180deg);
	}

	.active-filter-badge {
		background-color: var(--theme, #ed0006);
		color: #fff;
		font-size: 10px;
		font-weight: 800;
		border-radius: 999px;
		padding: 2px 6px;
		margin-left: auto;
		margin-right: 10px;
		line-height: 1;
	}

	.main-sidebar-1 :global(ul) {
		display: grid;
		gap: 2px;
		padding: 0 !important;
		margin: 0 !important;
		list-style: none !important;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li),
	.main-sidebar-1 :global(.single-sidebar-widget__widget-categories ul li) {
		padding: 0 !important;
		margin: 0 !important;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li a),
	.main-sidebar-1 :global(.single-sidebar-widget__widget-categories ul li a) {
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		gap: 10px;
		min-height: 34px;
		padding: 6px 8px !important;
		border-radius: 9px;
		color: #4b5563 !important;
		font-size: 13.5px !important;
		font-weight: 500 !important;
		line-height: 1.2 !important;
		text-decoration: none !important;
		transition:
			background-color 0.15s ease,
			color 0.15s ease;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li a:hover),
	.main-sidebar-1 :global(.single-sidebar-widget__widget-categories ul li a:hover) {
		background: #f7f9fb;
		color: #111827 !important;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li a.active),
	.main-sidebar-1 :global(.single-sidebar-widget__widget-categories ul li a.active) {
		background: color-mix(in srgb, var(--theme) 9%, #fff);
		color: var(--theme) !important;
		font-weight: 750 !important;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li a > i),
	.main-sidebar-1 :global(.single-sidebar-widget__widget-categories ul li a > i) {
		width: 12px;
		margin: 0 6px 0 0 !important;
		color: #9aa3b1;
		font-size: 10px !important;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li a.active > i),
	.main-sidebar-1 :global(.single-sidebar-widget__widget-categories ul li a.active > i) {
		color: var(--theme);
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li.parent-category-item) {
		margin-top: 3px !important;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li.parent-category-item a) {
		min-height: 38px;
		color: #243044 !important;
		font-size: 14px !important;
		font-weight: 700 !important;
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li.parent-category-item a.active) {
		background: color-mix(in srgb, var(--theme) 10%, #fff);
		box-shadow: inset 3px 0 0 var(--theme);
	}

	.main-sidebar-1 :global(.single-sidebar-widget__shop-widget-categories ul li a span:last-child),
	.main-sidebar-1 :global(.single-sidebar-widget__widget-categories ul li a span:last-child) {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-width: 24px;
		height: 22px;
		padding: 0 7px;
		border-radius: 999px;
		background: #f2f5f8;
		color: #667085;
		font-size: 11px;
		font-weight: 750;
	}

	:global(.single-sidebar-widget__shop-widget-categories ul li.subcategory-item) {
		position: relative;
		margin-left: 18px !important;
		padding-left: 14px !important;
	}

	:global(.single-sidebar-widget__shop-widget-categories ul li.subcategory-item::before) {
		content: '';
		position: absolute;
		top: -4px;
		bottom: -4px;
		left: 0;
		width: 1px;
		background: #dbe3ee;
	}

	:global(.single-sidebar-widget__shop-widget-categories ul li.subcategory-item a) {
		min-height: 32px !important;
		padding-left: 10px !important;
		background: #f8fafc;
		color: #64748b !important;
		font-size: 13px !important;
		font-weight: 600 !important;
	}

	:global(.single-sidebar-widget__shop-widget-categories ul li.subcategory-item a.active) {
		background: #ffffff;
		box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--theme) 28%, #e2e8f0);
		color: var(--theme) !important;
	}

	:global(.single-sidebar-widget__shop-widget-categories ul li.subcategory-item a i) {
		width: 14px !important;
		margin-right: 6px !important;
		color: #94a3b8;
		font-size: 9px !important;
		transform: rotate(90deg);
	}

	.mobile-market-header {
		display: none;
	}

	.filter-count-badge {
		min-width: 20px;
		height: 20px;
		padding: 0 6px;
		border-radius: 10px;
		background: var(--theme, #ed0006);
		color: #ffffff;
		font-size: 11px;
		line-height: 20px;
		text-align: center;
		font-weight: 700;
	}

	/* Filter Chips */
	.active-filter-chips {
		display: flex;
		align-items: center;
		gap: 6px;
		overflow-x: auto;
		white-space: nowrap;
		-webkit-overflow-scrolling: touch;
		scrollbar-width: none;
		padding: 4px 0;
	}

	.active-filter-chips::-webkit-scrollbar {
		display: none;
	}

	.filter-chip {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 4px 10px;
		background: #ffffff;
		border: 1px solid #cbd5e1;
		border-radius: 20px;
		font-size: 12px;
		color: #334155;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.filter-chip i {
		font-size: 10px;
		color: #94a3b8;
	}

	.filter-chip:hover {
		border-color: var(--theme, #ed0006);
		color: var(--theme, #ed0006);
	}

	.filter-chip:hover i {
		color: var(--theme, #ed0006);
	}

	.clear-all-chip {
		background: transparent;
		border: none;
		color: var(--theme, #ed0006);
		font-size: 12px;
		font-weight: 600;
		cursor: pointer;
		padding: 4px 6px;
		text-decoration: underline;
		white-space: nowrap;
	}

	/* Mobile Filter Drawer */
	.mobile-drawer-backdrop {
		position: fixed;
		inset: 0;
		background: rgba(15, 23, 42, 0.42);
		backdrop-filter: blur(4px);
		z-index: 1050;
		animation: fadeIn 0.2s ease;
	}

	.mobile-drawer-panel {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		width: 100vw;
		max-height: min(86vh, 760px);
		background: #ffffff;
		z-index: 1055;
		display: flex;
		flex-direction: column;
		border-radius: 24px 24px 0 0;
		box-shadow: 0 -18px 42px rgba(15, 23, 42, 0.18);
		animation: slideInUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
	}

	.mobile-drawer-panel::before {
		content: '';
		width: 44px;
		height: 4px;
		margin: 10px auto 0;
		border-radius: 999px;
		background: #d7dce6;
		flex: 0 0 auto;
	}

	.mobile-drawer-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 14px 18px;
		border-bottom: 1px solid #e2e8f0;
	}

	.mobile-drawer-reset-header-btn {
		background: transparent;
		border: none;
		color: var(--theme, #ed0006);
		font-weight: 700;
		font-size: 14px;
		cursor: pointer;
		padding: 4px 8px;
	}

	.mobile-drawer-reset-header-btn:hover {
		text-decoration: underline;
	}

	.mobile-drawer-close-btn {
		width: 32px;
		height: 32px;
		border: none;
		background: #f1f5f9;
		border-radius: 8px;
		color: #64748b;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.mobile-drawer-close-btn:hover {
		background: #e2e8f0;
		color: #0f172a;
	}

	.mobile-drawer-body {
		flex: 1;
		overflow-y: auto;
		padding: 14px 18px;
		-webkit-overflow-scrolling: touch;
	}

	.mobile-drawer-footer {
		display: flex;
		align-items: center;
		gap: 10px;
		padding: 12px 18px calc(14px + env(safe-area-inset-bottom));
		border-top: 1px solid #e2e8f0;
		background: #ffffff;
	}

	.btn-drawer-apply {
		flex: 2;
		height: 44px;
		border: none;
		background: var(--theme, #ed0006);
		border-radius: 10px;
		font-weight: 600;
		font-size: 14px;
		color: #ffffff;
		cursor: pointer;
		box-shadow: 0 4px 12px rgba(237, 0, 6, 0.25);
	}

	.btn-drawer-apply:hover {
		background: #d60005;
	}

	@keyframes fadeIn {
		from { opacity: 0; }
		to { opacity: 1; }
	}

	@keyframes slideInUp {
		from { transform: translateY(100%); }
		to { transform: translateY(0); }
	}

	@media (max-width: 991.98px) {
		:global(body.shop-mobile-page .breadcumb-section) {
			display: none;
		}

		:global(body.shop-mobile-page) {
			background: #f7f8fb;
		}

		:global(.section-padding2) {
			padding: 0 0 32px !important;
		}

		.shop-section {
			background: #f7f8fb;
		}

		.shop-section :global(.container) {
			max-width: 100%;
			padding-right: 10px;
			padding-left: 10px;
		}

		.mobile-market-header {
			display: block;
			margin: 0 -10px 10px;
			padding: 10px 10px 0;
		}

		.mobile-sticky-filters {
			position: sticky;
			top: 0;
			z-index: 20;
			margin: 0 -10px;
			padding: 8px 10px;
			background: rgba(247, 248, 251, 0.98);
			backdrop-filter: blur(16px);
			border-bottom: 1px solid rgba(15, 23, 42, 0.06);
		}

		.mobile-ad-banner {
			position: relative;
			display: flex;
			align-items: center;
			justify-content: flex-start;
			min-height: 82px;
			margin-bottom: 0;
			padding: 14px 16px;
			border-radius: 18px;
			background: linear-gradient(135deg, #07111f 0%, color-mix(in srgb, var(--theme) 45%, #07111f) 100%);
			color: #ffffff;
			overflow: hidden;
			text-decoration: none;
			box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
			isolation: isolate;
		}

		.mobile-ad-banner img {
			position: absolute;
			inset: 0;
			width: 100%;
			height: 100%;
			object-fit: cover;
			z-index: -2;
		}

		.mobile-ad-banner::after {
			content: '';
			position: absolute;
			inset: 0;
			z-index: -1;
			background: linear-gradient(90deg, rgba(7, 17, 31, 0.84), rgba(7, 17, 31, 0.24));
		}

		.mobile-ad-content {
			display: flex;
			flex-direction: column;
			align-items: flex-start;
			max-width: min(78%, 360px);
			gap: 3px;
		}

		.mobile-ad-content small {
			padding: 3px 8px;
			border-radius: 999px;
			background: rgba(255, 255, 255, 0.18);
			color: #ffffff;
			font-size: 10px;
			font-weight: 800;
			line-height: 1;
		}

		.mobile-ad-content strong {
			display: -webkit-box;
			overflow: hidden;
			color: #ffffff;
			font-size: 18px;
			font-weight: 900;
			line-height: 1.15;
			-webkit-box-orient: vertical;
			-webkit-line-clamp: 2;
			line-clamp: 2;
		}

		.mobile-ad-content em {
			display: -webkit-box;
			overflow: hidden;
			color: rgba(255, 255, 255, 0.84);
			font-size: 12px;
			font-style: normal;
			font-weight: 700;
			line-height: 1.2;
			-webkit-box-orient: vertical;
			-webkit-line-clamp: 1;
			line-clamp: 1;
		}

		.mobile-category-heading {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			margin-bottom: 8px;
			padding: 0 4px;
			color: #17213b;
		}

		.mobile-category-heading strong {
			font-size: 14px;
			font-weight: 800;
			line-height: 1.1;
		}

		.mobile-category-heading span {
			color: #8a8f9c;
			font-size: 10px;
			white-space: nowrap;
		}

		.mobile-category-strip,
		.mobile-subcategory-strip,
		.mobile-filter-chips {
			display: flex;
			flex-wrap: nowrap;
			gap: 8px;
			overflow-x: auto;
			overflow-y: hidden;
			-webkit-overflow-scrolling: touch;
			touch-action: pan-x;
			overscroll-behavior-x: contain;
			scrollbar-width: none;
		}

		.mobile-category-strip::-webkit-scrollbar,
		.mobile-subcategory-strip::-webkit-scrollbar,
		.mobile-filter-chips::-webkit-scrollbar {
			display: none;
		}

		.mobile-category-strip {
			margin-bottom: 9px;
			padding: 0 4px 4px;
			width: 100%;
			box-sizing: border-box;
		}

		.mobile-subcategory-strip {
			margin: -2px 0 9px;
			padding: 0 4px 4px;
			width: 100%;
			box-sizing: border-box;
		}

		.mobile-subcategory-chip {
			flex: 0 0 auto;
			flex-shrink: 0;
			height: 30px;
			padding: 0 12px;
			border: 0;
			border-radius: 999px;
			background: #ffffff;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.07);
			color: #334155;
			font-size: 10.5px;
			font-weight: 800;
			white-space: nowrap;
			touch-action: pan-x;
			user-select: none;
		}

		.mobile-subcategory-chip.active {
			background: #fff0f7;
			color: #e91e79;
			box-shadow: inset 0 0 0 1px rgba(233, 30, 121, 0.35), 0 4px 12px rgba(15, 23, 42, 0.07);
		}

		.mobile-category-card {
			flex: 0 0 68px;
			flex-shrink: 0;
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 5px;
			min-width: 68px;
			height: 72px;
			padding: 6px 5px;
			border: 0;
			border-radius: 16px;
			background: #ffffff;
			box-shadow: 0 5px 14px rgba(15, 23, 42, 0.08);
			color: #17213b;
			touch-action: pan-x;
			user-select: none;
		}

		.mobile-category-card.active {
			box-shadow: inset 0 0 0 1.5px #ec2c79, 0 5px 14px rgba(15, 23, 42, 0.08);
		}

		.mobile-category-thumb {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 39px;
			height: 32px;
			border-radius: 9px;
			background: #f3f5f9;
			overflow: hidden;
		}

		.mobile-category-thumb img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.mobile-category-thumb i {
			color: #9398a5;
			font-size: 17px;
		}

		.mobile-category-card small {
			display: -webkit-box;
			max-width: 100%;
			overflow: hidden;
			color: #17213b;
			font-size: 8.5px;
			font-weight: 800;
			line-height: 1.15;
			text-align: center;
			-webkit-box-orient: vertical;
			-webkit-line-clamp: 2;
			line-clamp: 2;
		}

		.mobile-filter-chips {
			align-items: center;
			gap: 8px;
			min-height: 42px;
			padding: 0 4px 6px;
		}

		.mobile-chip,
		.mobile-filter-select {
			flex: 0 0 auto;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			height: 40px;
			min-height: 40px;
			box-sizing: border-box;
			line-height: 1;
			vertical-align: middle;
			margin: 0;
			border: 1px solid transparent;
			border-radius: 999px;
			background: #e9ebf1;
			box-shadow: none;
		}

		.mobile-chip {
			gap: 7px;
			padding: 0 14px;
			color: #17213b;
			font-size: 13px;
			font-weight: 800;
			white-space: nowrap;
		}

		.mobile-chip i {
			color: #4c5870;
			font-size: 14px;
		}

		.mobile-filter-select {
			position: relative;
			gap: 7px;
			max-width: 197px;
			padding: 0 12px 0 14px;
			color: #17213b;
			font-size: 12.5px;
			font-weight: 800;
			white-space: nowrap;
		}

		.mobile-filter-select span {
			color: #4b5563;
			font-size: 12px;
			font-weight: 800;
		}

		.mobile-filter-select i {
			color: #4c5870;
			font-size: 14px;
			flex: 0 0 auto;
		}

		.mobile-sort-select {
			position: relative;
			width: 40px;
			max-width: 40px;
			min-width: 40px;
			justify-content: center;
			padding: 0;
		}

		.mobile-sort-select select {
			position: absolute;
			inset: 0;
			width: 100%;
			min-width: 0;
			max-width: none;
			height: 100%;
			opacity: 0;
			cursor: pointer;
		}

		.mobile-filter-select select {
			max-width: 113px;
			min-width: 70px;
			border: 0;
			outline: 0;
			background: transparent;
			color: #17213b;
			font-size: 13px;
			font-weight: 800;
			line-height: 1;
			text-overflow: ellipsis;
		}

		.mobile-sort-select select {
			position: absolute;
			inset: 0;
			width: 100%;
			min-width: 0;
			max-width: none;
			height: 100%;
			opacity: 0;
			cursor: pointer;
		}

		.filter-count-badge {
			min-width: 16px;
			height: 16px;
			padding: 0 4px;
			font-size: 9px;
			line-height: 16px;
		}

		.active-filter-chips {
			padding: 7px 4px 0;
		}

		.shop-section :global(.row.g-2) {
			--bs-gutter-x: 8px;
			--bs-gutter-y: 8px;
		}

		.shop-section :global(.col-6) {
			padding-right: 4px;
			padding-left: 4px;
		}

		.shop-section :global(.col-lg-9) {
			padding-right: 8px;
			padding-left: 8px;
		}

		.shop-section :global(.product-card),
		.shop-section :global(.featured-product-item-one),
		.shop-section :global(.best-seller-product-items-two) {
			border-radius: 11px !important;
		}

		.shop-section :global(.best-seller-product-items-two__thumb) {
			height: 170px;
		}

		.shop-section :global(.best-seller-product-items-two__content) {
			padding: 6px 4px;
		}

		.shop-section :global(.best-seller-product-items-two__details--title a) {
			font-size: 10.5px;
			line-height: 1.22;
		}

		.shop-section :global(.best-seller-product-items-two__details--subtitle),
		.shop-section :global(.best-seller-product-items-two__details--price) {
			font-size: 10px;
			line-height: 1.2;
		}

		.shop-section :global(.best-seller-product-items-two .icon-box2) {
			top: 6px !important;
			inset-inline-end: 6px !important;
			gap: 5px !important;
		}

		.shop-section :global(.best-seller-product-items-two .icon-box2 .fav-btn),
		.shop-section :global(.best-seller-product-items-two .icon-box2 .add-to-cart-btn) {
			width: 30px !important;
			height: 30px !important;
			font-size: 12.5px !important;
		}
	}

	@media (min-width: 768px) and (max-width: 991.98px) {
		:global(.section-padding2) {
			padding: 18px 0 44px !important;
		}

		.shop-section :global(.container) {
			padding-right: 18px;
			padding-left: 18px;
		}

		.mobile-market-header {
			margin: 0 -18px 18px;
			padding: 12px 18px 10px;
		}

		.mobile-ad-banner {
			min-height: 64px;
			border-radius: 20px;
			padding: 16px 18px;
		}

		.mobile-ad-content strong {
			font-size: 20px;
		}

		.mobile-ad-content em {
			font-size: 13px;
		}

		.mobile-category-card {
			flex-basis: 88px;
			min-width: 88px;
			height: 88px;
			padding: 8px 7px;
		}

		.mobile-category-thumb {
			width: 48px;
			height: 40px;
		}

		.mobile-category-card small {
			font-size: 10px;
		}

		.shop-section :global(.row.g-2) {
			--bs-gutter-x: 14px;
			--bs-gutter-y: 14px;
		}

		.shop-section :global(.col-6),
		.shop-section :global(.col-lg-9) {
			padding-right: calc(var(--bs-gutter-x) * 0.5);
			padding-left: calc(var(--bs-gutter-x) * 0.5);
		}

		.shop-section :global(.best-seller-product-items-two) {
			border-radius: 14px !important;
		}

		.shop-section :global(.best-seller-product-items-two__thumb) {
			height: 220px;
		}

		.shop-section :global(.best-seller-product-items-two__content) {
			padding: 10px 8px;
		}

		.shop-section :global(.best-seller-product-items-two__details--title a) {
			font-size: 13px;
		}

		.shop-section :global(.best-seller-product-items-two__details--subtitle),
		.shop-section :global(.best-seller-product-items-two__details--price) {
			font-size: 12px;
		}
	}

	@media (min-width: 768px) {
		.shop-loader-grid {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}

		.loader-image {
			height: 190px;
		}

		.shop-section :global(.best-seller-product-items-two__thumb) {
			height: 190px;
		}

		.shop-section :global(.best-seller-product-items-two__content) {
			padding: 8px;
		}

		.shop-section :global(.best-seller-product-items-two .icon-box2 .fav-btn),
		.shop-section :global(.best-seller-product-items-two .icon-box2 .add-to-cart-btn) {
			width: 32px !important;
			height: 32px !important;
			font-size: 12px !important;
		}
	}

	@media (min-width: 1200px) {
		.shop-loader-grid {
			grid-template-columns: repeat(4, minmax(0, 1fr));
		}

		.loader-image {
			height: 210px;
		}

		.shop-section :global(.best-seller-product-items-two__thumb) {
			height: 210px;
		}
	}
</style>
