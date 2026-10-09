<script lang="ts">
	import { onMount } from 'svelte';
	import { categories, categoryName, loadCategories } from '$lib/services/categories';
	import { locale, translate } from '$lib/i18n';

	onMount(() => loadCategories());

	// Only the categories flagged 'show on home' (all, if none are flagged).
	const shown = $derived($categories.some((c) => c.show_on_home) ? $categories.filter((c) => c.show_on_home) : $categories);

	// Fallback pastel palette if category has no custom background_color set in admin
	const DEFAULT_PALETTE = [
		'#fde8e1', // soft peach
		'#e0f2fe', // soft sky blue
		'#e6f4ea', // soft mint
		'#e0f2f1', // soft teal
		'#f3e8ff', // soft lavender
		'#e0f2fe', // soft baby blue
		'#fce7f3', // soft pink
		'#f3e8ff', // soft lilac
		'#e8f5e9', // soft green
		'#fef3c7', // soft warm yellow
	];

	function getBgColor(category: (typeof shown)[number], index: number): string {
		if (category.background_color && category.background_color.trim()) {
			return category.background_color;
		}
		return DEFAULT_PALETTE[index % DEFAULT_PALETTE.length];
	}
</script>

{#if shown.length > 0}
	<section class="home-category-strip" aria-label={$translate('Categories')}>
		<div class="container">
			<div class="home-category-grid">
				{#each shown as category, index (category.id)}
					<a
						class="home-category-card"
						href={`/shop?category=${category.id}`}
						style="--card-bg: {getBgColor(category, index)}"
					>
						<span class="home-category-name">{categoryName(category, $locale)}</span>
						<span class="home-category-thumb">
							{#if category.image}
								<img src={category.image} alt={categoryName(category, $locale)} loading="lazy" />
							{:else}
								<i class="fa-regular fa-grid-2"></i>
							{/if}
						</span>
					</a>
				{/each}
			</div>
		</div>
	</section>
{/if}

<style>
	.home-category-strip {
		background: #ffffff;
		padding: 24px 0 32px;
	}

	.home-category-grid {
		display: grid;
		grid-template-columns: repeat(10, minmax(0, 1fr));
		gap: 12px;
		max-width: var(--home-content-width, 1540px);
		margin: 0 auto;
	}

	.home-category-card {
		position: relative;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		height: 124px;
		padding: 12px 10px 8px;
		background-color: var(--card-bg, #f1f5f9);
		border-radius: 16px;
		text-decoration: none;
		color: #1f2937;
		overflow: hidden;
		transition: transform 0.2s cubic-bezier(0.2, 0, 0, 1), box-shadow 0.2s ease;
		user-select: none;
	}

	.home-category-card:hover {
		transform: translateY(-3px);
		box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
	}

	.home-category-name {
		font-size: 13px;
		font-weight: 600;
		line-height: 1.25;
		color: #1e2530;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		line-clamp: 2;
		overflow: hidden;
		word-break: break-word;
		z-index: 2;
	}

	.home-category-thumb {
		position: relative;
		align-self: flex-end;
		width: 64px;
		height: 64px;
		display: flex;
		align-items: flex-end;
		justify-content: flex-end;
		margin-top: auto;
		z-index: 1;
	}

	.home-category-thumb img {
		max-width: 100%;
		max-height: 100%;
		object-fit: contain;
		display: block;
		transition: transform 0.25s ease;
		filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.08));
	}

	.home-category-thumb i {
		font-size: 32px;
		color: rgba(30, 41, 59, 0.25);
		margin-bottom: 4px;
		margin-right: 4px;
	}

	.home-category-card:hover .home-category-thumb img {
		transform: scale(1.08);
	}

	@media (max-width: 1400px) {
		.home-category-grid {
			grid-template-columns: repeat(8, minmax(0, 1fr));
			gap: 10px;
		}

		.home-category-card {
			height: 118px;
			padding: 10px 8px 6px;
		}

		.home-category-thumb {
			width: 58px;
			height: 58px;
		}
	}

	@media (max-width: 1199.98px) {
		.home-category-grid {
			grid-template-columns: repeat(6, minmax(0, 1fr));
			gap: 10px;
		}

		.home-category-card {
			height: 114px;
		}

		.home-category-thumb {
			width: 54px;
			height: 54px;
		}
	}

	@media (max-width: 991.98px) {
		.home-category-grid {
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 10px;
		}

		.home-category-card {
			height: 110px;
			border-radius: 14px;
		}
	}

	@media (max-width: 575.98px) {
		.home-category-strip {
			padding: 14px 0 20px;
		}

		.home-category-grid {
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 8px;
		}

		.home-category-card {
			height: 98px;
			padding: 8px 6px 4px;
			border-radius: 12px;
		}

		.home-category-name {
			font-size: 11.5px;
			line-height: 1.2;
		}

		.home-category-thumb {
			width: 44px;
			height: 44px;
		}

		.home-category-thumb i {
			font-size: 24px;
		}
	}
</style>
