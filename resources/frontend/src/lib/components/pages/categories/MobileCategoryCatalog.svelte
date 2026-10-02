<script lang="ts">
	import { onMount } from 'svelte';
	import { categories, categoryName, loadCategories } from '$lib/services/categories';
	import { locale, translate } from '$lib/i18n';

	onMount(() => {
		loadCategories();
	});
</script>

<section class="category-catalog-page">
	<div class="catalog-shell">
		<div class="catalog-header mb-4">
			<h1>{$translate('Catalog')}</h1>
		</div>

		<div class="category-grid" aria-label={$translate('Categories')}>
			{#each $categories as category (category.id)}
				<a class="category-card" href={`/shop?category=${category.id}`}>
					<div class="category-image">
						{#if category.image}
							<img src={category.image} alt={categoryName(category, $locale)} loading="lazy" />
						{:else}
							<div class="category-fallback-icon">
								<i class="fa-regular fa-grid-2"></i>
							</div>
						{/if}
					</div>
					<div class="category-info">
						<span class="category-title">{categoryName(category, $locale)}</span>
						{#if category.products_count !== undefined && category.products_count > 0}
							<span class="category-count">{$translate('{count} products', { count: category.products_count })}</span>
						{/if}
					</div>
				</a>
			{/each}
		</div>
	</div>
</section>

<style>
	.category-catalog-page {
		min-height: calc(100vh - 120px);
		background: #f7f8fb;
		padding: 24px 12px 84px;
	}

	.catalog-shell {
		max-width: 1200px;
		margin: 0 auto;
	}

	.catalog-header h1 {
		margin: 0;
		color: #111827;
		font-size: 26px;
		font-weight: 800;
		line-height: 1.2;
	}

	.category-grid {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 12px;
	}

	.category-card {
		display: flex;
		flex-direction: column;
		background: #ffffff;
		border-radius: 14px;
		overflow: hidden;
		text-decoration: none;
		color: #111827;
		border: 1px solid rgba(15, 23, 42, 0.06);
		box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
		transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
	}

	.category-card:hover {
		transform: translateY(-4px);
		box-shadow: 0 12px 28px rgba(15, 23, 42, 0.09);
		border-color: rgba(255, 64, 53, 0.35);
	}

	.category-image {
		position: relative;
		width: 100%;
		aspect-ratio: 4 / 3;
		overflow: hidden;
		background: #f1f5f9;
	}

	.category-image img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
		transition: transform 0.4s ease;
	}

	.category-card:hover .category-image img {
		transform: scale(1.08);
	}

	.category-fallback-icon {
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #94a3b8;
		font-size: 28px;
	}

	.category-info {
		padding: 12px 10px;
		text-align: center;
		display: flex;
		flex-direction: column;
		gap: 2px;
	}

	.category-title {
		font-size: 14px;
		font-weight: 700;
		color: #111827;
		line-height: 1.3;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		line-clamp: 2;
		overflow: hidden;
		transition: color 0.2s;
	}

	.category-card:hover .category-title {
		color: #ff4035;
	}

	.category-count {
		font-size: 12px;
		color: #64748b;
	}

	@media (min-width: 576px) {
		.category-grid {
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 16px;
		}
	}

	@media (min-width: 992px) {
		.category-catalog-page {
			padding: 40px 24px 90px;
		}

		.catalog-header h1 {
			font-size: 30px;
		}

		.category-grid {
			grid-template-columns: repeat(6, minmax(0, 1fr));
			gap: 20px;
		}

		.category-info {
			padding: 14px 12px;
		}

		.category-title {
			font-size: 15px;
		}
	}

	@media (max-width: 480px) {
		.category-grid {
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 8px;
		}

		.category-card {
			border-radius: 10px;
		}

		.category-info {
			padding: 8px 6px;
		}

		.category-title {
			font-size: 12px;
		}

		.category-count {
			display: none;
		}
	}
</style>
