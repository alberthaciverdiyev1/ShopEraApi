<script lang="ts">
	import { onMount } from 'svelte';
	import { categories, categoriesError, categoriesLoading, categoryName, loadCategories } from '$lib/services/categories';
	import { locale, translate } from '$lib/i18n';

	onMount(() => loadCategories());
</script>

<ul class="sub-cataegory">
	{#if $categoriesError}
		<li><a href="#">{$categoriesError}</a></li>
	{:else if $categoriesLoading && $categories.length === 0}
		<li><a href="#">{$translate('Loading...')}</a></li>
	{:else}
		{#each $categories as category (category.id)}
			{#if category.children.length}
				<li class="sub-has-dropdown">
					<a href={`/shop?category=${category.id}`}>
						{categoryName(category, $locale)} <i class="fas fa-angle-right"></i>
					</a>
					<ul class="sub-cataegory">
						{#each category.children as child (child.id)}
							<li><a href={`/shop?category=${child.id}`}>{categoryName(child, $locale)}</a></li>
						{/each}
					</ul>
				</li>
			{:else}
				<li>
					<a href={`/shop?category=${category.id}`}>{categoryName(category, $locale)}</a>
				</li>
			{/if}
		{/each}
	{/if}
</ul>
