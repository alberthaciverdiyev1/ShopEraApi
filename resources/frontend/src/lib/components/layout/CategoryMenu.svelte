<script lang="ts">
	import { onMount } from 'svelte';
	import { categories, categoriesError, categoriesLoading, categoryName, loadCategories, type Category } from '$lib/services/categories';
	import { locale, translate } from '$lib/i18n';

	let activeParentId = $state<number | null>(null);
	let activeChildId = $state<number | null>(null);

	const activeParent = $derived.by(() => $categories.find((category) => category.id === activeParentId) ?? null);
	const activeChild = $derived.by(() => {
		if (!activeParent?.children.length) return null;
		return activeParent.children.find((child) => child.id === activeChildId) ?? null;
	});
	const leafCategories = $derived(activeChild?.children ?? []);
	const panelColumns = $derived(1 + (activeParent ? 1 : 0) + (activeChild && leafCategories.length ? 1 : 0));

	function setParent(category: Category) {
		if (!category.children.length) {
			window.location.href = `/shop?category=${category.id}`;
			return;
		}

		activeParentId = activeParentId === category.id ? null : category.id;
		activeChildId = null;
	}

	function setChild(category: Category) {
		if (!category.children.length) {
			window.location.href = `/shop?category=${category.id}`;
			return;
		}

		activeChildId = activeChildId === category.id ? null : category.id;
	}

	onMount(async () => {
		await loadCategories();
	});
</script>

<div class="sub-cataegory catalog-mega-panel columns-{panelColumns}">
	{#if $categoriesError}
		<div class="catalog-menu-message">{$categoriesError}</div>
	{:else if $categoriesLoading && $categories.length === 0}
		<div class="catalog-menu-message">{$translate('Loading...')}</div>
	{:else}
		<div class="catalog-column catalog-column-primary" aria-label={$translate('Categories')}>
			{#each $categories as category (category.id)}
				<a
					class="catalog-row"
					class:active={activeParent?.id === category.id}
					href={`/shop?category=${category.id}`}
					onclick={(event) => {
						if (category.children.length) {
							event.preventDefault();
							setParent(category);
						}
					}}
				>
					<span class="catalog-thumb">
						{#if category.image}
							<img src={category.image} alt="" loading="lazy" />
						{:else}
							<i class="fa-regular fa-grid-2"></i>
						{/if}
					</span>
					<span>{categoryName(category, $locale)}</span>
					{#if category.children.length}
						<i class="fa-solid fa-chevron-right catalog-chevron"></i>
					{/if}
				</a>
			{/each}
		</div>

		{#if activeParent}
			<div class="catalog-column" aria-label={$translate('Subcategory')}>
				<a class="catalog-row catalog-row-text catalog-view-all" href={`/shop?category=${activeParent.id}`}>
					<span>{categoryName(activeParent, $locale)}</span>
					<i class="fa-solid fa-chevron-right catalog-chevron"></i>
				</a>
				{#each activeParent.children as child (child.id)}
					<a
						class="catalog-row catalog-row-text"
						class:active={activeChild?.id === child.id}
						href={`/shop?category=${child.id}`}
						onclick={(event) => {
							if (child.children.length) {
								event.preventDefault();
								setChild(child);
							}
						}}
					>
						<span>{categoryName(child, $locale)}</span>
						{#if child.children.length}
							<i class="fa-solid fa-chevron-right catalog-chevron"></i>
						{/if}
					</a>
				{/each}
			</div>
		{/if}

		{#if activeChild && leafCategories.length}
			<div class="catalog-column catalog-column-leaf" aria-label={$translate('Subcategory')}>
				<a class="catalog-leaf-link catalog-leaf-link-featured" href={`/shop?category=${activeChild.id}`}>
					{categoryName(activeChild, $locale)}
				</a>
				{#each leafCategories as leaf (leaf.id)}
					<a class="catalog-leaf-link" href={`/shop?category=${leaf.id}`}>
						{categoryName(leaf, $locale)}
					</a>
				{/each}
			</div>
		{/if}
	{/if}
</div>
