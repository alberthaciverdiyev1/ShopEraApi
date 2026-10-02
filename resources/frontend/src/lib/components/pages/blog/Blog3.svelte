<script lang="ts">
	import { onMount } from 'svelte';
	import BlogCard from '$lib/components/cards/BlogCard.svelte';
	import { fetchBlogs, type ApiBlog, type BlogCategory, type BlogMeta } from '$lib/services/blog';
	import { translate } from '$lib/i18n';

	let loading = $state(true);
	let blogs = $state<ApiBlog[]>([]);
	let categories = $state<BlogCategory[]>([]);
	let selectedCategory = $state<string>('');
	let searchQuery = $state<string>('');
	let currentPage = $state<number>(1);
	let meta = $state<BlogMeta>({
		current_page: 1,
		last_page: 1,
		per_page: 9,
		total: 0
	});

	async function loadBlogs(page = 1, category = selectedCategory, search = searchQuery) {
		loading = true;
		try {
			const res = await fetchBlogs({
				page,
				per_page: 9,
				category: category || undefined,
				search: search ? search.trim() : undefined
			});
			blogs = res.items || [];
			meta = res.meta || { current_page: page, last_page: 1, per_page: 9, total: blogs.length };
			currentPage = meta.current_page;
			if (res.sidebar?.categories && categories.length === 0) {
				categories = res.sidebar.categories;
			}
		} catch (err) {
			console.error('Failed to load blogs:', err);
			blogs = [];
		} finally {
			loading = false;
		}
	}

	function selectCategory(catName: string) {
		selectedCategory = catName;
		currentPage = 1;
		loadBlogs(1, catName, searchQuery);
	}

	function handleSearch(e: Event) {
		e.preventDefault();
		currentPage = 1;
		loadBlogs(1, selectedCategory, searchQuery);
	}

	function goToPage(page: number) {
		if (page < 1 || page > meta.last_page || page === currentPage) return;
		currentPage = page;
		loadBlogs(page, selectedCategory, searchQuery);
		if (typeof window !== 'undefined') {
			window.scrollTo({ top: 120, behavior: 'smooth' });
		}
	}

	onMount(() => {
		loadBlogs();
	});
</script>

<section class="blog-page-section section-padding fix">
	<div class="container">
		<!-- Filter & Search Toolbar -->
		<div class="blog-toolbar mb-4">
			<div class="row align-items-center g-3">
				<div class="col-lg-8 col-md-7">
					<div class="category-pills">
						<button
							type="button"
							class="pill-btn"
							class:active={!selectedCategory}
							onclick={() => selectCategory('')}
						>
							{$translate('All')}
						</button>
						{#each categories as cat (cat.name)}
							<button
								type="button"
								class="pill-btn"
								class:active={selectedCategory === cat.name}
								onclick={() => selectCategory(cat.name)}
							>
								{cat.name}
								{#if cat.count}
									<span class="cat-count">({cat.count})</span>
								{/if}
							</button>
						{/each}
					</div>
				</div>
				<div class="col-lg-4 col-md-5">
					<form class="blog-search-form" onsubmit={handleSearch}>
						<input
							type="text"
							placeholder={$translate('Search articles...')}
							bind:value={searchQuery}
						/>
						<button type="submit" aria-label="Search">
							<i class="fa-solid fa-magnifying-glass"></i>
						</button>
					</form>
				</div>
			</div>
		</div>

		<!-- Blog Grid -->
		{#if loading}
			<div class="row g-3 g-md-4">
				{#each Array(6) as _, i (i)}
					<div class="col-xl-4 col-md-6 col-6">
						<div class="blog-skeleton">
							<div class="skeleton-thumb"></div>
							<div class="skeleton-content">
								<div class="skeleton-line w-75"></div>
								<div class="skeleton-line w-50"></div>
							</div>
						</div>
					</div>
				{/each}
			</div>
		{:else if blogs.length === 0}
			<div class="empty-blog-state text-center py-5">
				<div class="empty-icon mb-3">
					<i class="fa-regular fa-newspaper fa-3x text-muted"></i>
				</div>
				<h4>{$translate('No articles found')}</h4>
				<p class="text-muted">
					{$translate('Try changing your search terms or filter selection.')}
				</p>
				{#if selectedCategory || searchQuery}
					<button
						type="button"
						class="theme-btn-2 mt-3"
						onclick={() => {
							selectedCategory = '';
							searchQuery = '';
							loadBlogs(1, '', '');
						}}
					>
						{$translate('Clear Filters')}
					</button>
				{/if}
			</div>
		{:else}
			<div class="row g-3 g-md-4">
				{#each blogs as blog (blog.id)}
					<div class="col-xl-4 col-md-6 col-6">
						<BlogCard {blog} />
					</div>
				{/each}
			</div>

			<!-- Pagination -->
			{#if meta.last_page > 1}
				<div class="pagination-wrapper mt-5">
					<div class="pagination">
						<button
							type="button"
							class="prev"
							class:disabled={currentPage <= 1}
							disabled={currentPage <= 1}
							onclick={() => goToPage(currentPage - 1)}
							aria-label="Previous"
						>
							<i class="fa-solid fa-chevron-left"></i>
						</button>

						{#each Array(meta.last_page) as _, idx (idx)}
							{@const pageNum = idx + 1}
							<button
								type="button"
								class="page"
								class:active={pageNum === currentPage}
								onclick={() => goToPage(pageNum)}
							>
								{pageNum < 10 ? `0${pageNum}` : pageNum}
							</button>
						{/each}

						<button
							type="button"
							class="next"
							class:disabled={currentPage >= meta.last_page}
							disabled={currentPage >= meta.last_page}
							onclick={() => goToPage(currentPage + 1)}
							aria-label="Next"
						>
							<i class="fa-solid fa-chevron-right"></i>
						</button>
					</div>
				</div>
			{/if}
		{/if}
	</div>
</section>

<style>
	.blog-page-section {
		padding-top: 50px;
		padding-bottom: 80px;
		background: #fbfbfb;
	}

	.category-pills {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		align-items: center;
	}

	.pill-btn {
		display: inline-flex;
		align-items: center;
		padding: 7px 16px;
		border-radius: 999px;
		background: #ffffff;
		border: 1px solid #e5e7eb;
		color: #4b5563;
		font-size: 14px;
		font-weight: 500;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.pill-btn:hover {
		border-color: #ff4035;
		color: #ff4035;
	}

	.pill-btn.active {
		background: #ff4035;
		border-color: #ff4035;
		color: #ffffff;
	}

	.cat-count {
		font-size: 12px;
		opacity: 0.8;
		margin-left: 4px;
	}

	.blog-search-form {
		position: relative;
		display: flex;
		width: 100%;
	}

	.blog-search-form input {
		width: 100%;
		height: 44px;
		padding: 0 46px 0 16px;
		border-radius: 999px;
		border: 1px solid #e5e7eb;
		background: #ffffff;
		font-size: 14px;
		outline: none;
		transition: border-color 0.2s ease;
	}

	.blog-search-form input:focus {
		border-color: #ff4035;
	}

	.blog-search-form button {
		position: absolute;
		right: 4px;
		top: 4px;
		width: 36px;
		height: 36px;
		border-radius: 50%;
		border: none;
		background: #ff4035;
		color: #ffffff;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		font-size: 13px;
		transition: opacity 0.2s;
	}

	.blog-search-form button:hover {
		opacity: 0.9;
	}

	/* Skeletons */
	.blog-skeleton {
		background: #ffffff;
		border-radius: 14px;
		overflow: hidden;
		box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
	}

	.skeleton-thumb {
		width: 100%;
		aspect-ratio: 16 / 10;
		background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
		background-size: 200% 100%;
		animation: pulse 1.5s infinite;
	}

	.skeleton-content {
		padding: 16px;
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.skeleton-line {
		height: 14px;
		border-radius: 4px;
		background: #ececec;
	}

	@keyframes pulse {
		0% {
			background-position: 200% 0;
		}
		100% {
			background-position: -200% 0;
		}
	}

	/* Pagination */
	.pagination-wrapper {
		display: flex;
		justify-content: center;
	}

	.pagination {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.pagination button {
		min-width: 40px;
		height: 40px;
		padding: 0 10px;
		border-radius: 8px;
		border: 1px solid #e5e7eb;
		background: #ffffff;
		color: #374151;
		font-size: 14px;
		font-weight: 600;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.pagination button:hover:not(:disabled) {
		border-color: #ff4035;
		color: #ff4035;
	}

	.pagination button.active {
		background: #ff4035;
		border-color: #ff4035;
		color: #ffffff;
	}

	.pagination button:disabled {
		opacity: 0.4;
		cursor: not-allowed;
	}

	@media (max-width: 575px) {
		.blog-page-section {
			padding-top: 25px;
			padding-bottom: 50px;
		}

		.category-pills {
			flex-wrap: nowrap;
			overflow-x: auto;
			padding-bottom: 6px;
			-webkit-overflow-scrolling: touch;
		}

		.pill-btn {
			white-space: nowrap;
			padding: 5px 12px;
			font-size: 12px;
		}

		.blog-search-form input {
			height: 38px;
			font-size: 13px;
		}

		.blog-search-form button {
			width: 30px;
			height: 30px;
		}

		.pagination button {
			min-width: 34px;
			height: 34px;
			font-size: 12px;
			border-radius: 6px;
		}
	}
</style>
