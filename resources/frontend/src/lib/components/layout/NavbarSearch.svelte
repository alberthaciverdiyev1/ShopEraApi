<script lang="ts">
	import { goto } from '$app/navigation';
	import { fetchShopProducts } from '$lib/services/shop';
	import { productImage, productTitle, productUrl, type ApiProduct } from '$lib/services/products';
	import { translate } from '$lib/i18n';

	let isOpen = $state(false);
	let query = $state('');
	let results = $state<ApiProduct[]>([]);
	let total = $state(0);
	let loading = $state(false);
	let hasSearched = $state(false);

	let inputEl = $state<HTMLInputElement | null>(null);
	let containerEl = $state<HTMLElement | null>(null);
	let debounceTimer: ReturnType<typeof setTimeout> | undefined;

	function toggleSearch(event?: MouseEvent) {
		event?.stopPropagation();
		isOpen = !isOpen;
		if (isOpen) {
			setTimeout(() => inputEl?.focus(), 80);
		} else {
			closeSearch();
		}
	}

	function closeSearch() {
		isOpen = false;
		query = '';
		results = [];
		hasSearched = false;
		clearTimeout(debounceTimer);
	}

	function handleInput() {
		clearTimeout(debounceTimer);
		const q = query.trim();

		if (q.length < 2) {
			results = [];
			hasSearched = false;
			loading = false;
			return;
		}

		loading = true;
		debounceTimer = setTimeout(async () => {
			try {
				const response = await fetchShopProducts({ search: q, per_page: 5 });
				results = response.items;
				total = response.meta.total;
				hasSearched = true;
			} catch (e) {
				console.error('Navbar search error:', e);
				results = [];
			} finally {
				loading = false;
			}
		}, 250);
	}

	function handleSubmit(event: SubmitEvent) {
		event.preventDefault();
		const q = query.trim();
		if (!q) return;
		closeSearch();
		goto(`/shop?search=${encodeURIComponent(q)}`);
	}

	function handleProductClick(url: string) {
		closeSearch();
		goto(url);
	}

	function handleWindowClick(event: MouseEvent) {
		if (!isOpen || !containerEl) return;
		const path = event.composedPath ? event.composedPath() : [];
		if (path.length > 0 && path.includes(containerEl)) return;
		if (containerEl.contains(event.target as Node)) return;
		closeSearch();
	}

	function handleKeydown(event: KeyboardEvent) {
		if (event.key === 'Escape') {
			closeSearch();
		}
	}
</script>

<svelte:window onclick={handleWindowClick} onkeydown={handleKeydown} />

<div class="navbar-search-wrapper" bind:this={containerEl} onclick={(e) => e.stopPropagation()}>
	{#if !isOpen}
		<button
			type="button"
			class="search-trigger-btn"
			onclick={toggleSearch}
			aria-label={$translate('Search products or categories...')}
			title={$translate('Search products...')}
		>
			<i class="fal fa-search"></i>
		</button>
	{:else}
		<div class="search-bar-inline">
			<form class="search-form" onsubmit={handleSubmit}>
				<i class="fal fa-search search-form-icon"></i>
				<input
					bind:this={inputEl}
					type="text"
					class="search-input"
					placeholder={$translate('Search products or categories...')}
					bind:value={query}
					oninput={handleInput}
					aria-label={$translate('Search products...')}
				/>

				{#if loading}
					<span class="search-spinner" aria-label={$translate('Loading...')}>
						<i class="fa-solid fa-spinner fa-spin"></i>
					</span>
				{:else if query}
					<button
						type="button"
						class="search-clear-btn"
						onclick={() => {
							query = '';
							results = [];
							hasSearched = false;
							inputEl?.focus();
						}}
						aria-label={$translate('Clear all')}
					>
						<i class="fa-solid fa-xmark"></i>
					</button>
				{/if}

				<button
					type="button"
					class="search-close-btn"
					onclick={closeSearch}
					aria-label={$translate('Close')}
					title={$translate('Close')}
				>
					<i class="fa-regular fa-times"></i>
				</button>
			</form>

			<!-- Canlı Nəticələr Pəncərəsi (Dropdown) -->
			{#if isOpen && query.trim().length >= 2}
				<div class="search-dropdown-menu">
					{#if loading && !results.length}
						<div class="search-status-box">
							<i class="fa-solid fa-spinner fa-spin text-muted me-2"></i>
							<span>{$translate('Loading...')}</span>
						</div>
					{:else if hasSearched && !results.length}
						<div class="search-status-box">
							<i class="fa-regular fa-face-frown text-muted me-2"></i>
							<span>{$translate('No matching products found')}</span>
						</div>
					{:else if results.length}
						<div class="dropdown-header">
							<span>{$translate('Products')} ({total})</span>
						</div>
						<div class="results-list">
							{#each results as item (item.id)}
								<a
									href={productUrl(item)}
									class="result-item"
									onclick={(e) => {
										e.preventDefault();
										handleProductClick(productUrl(item));
									}}
								>
									<img
										src={productImage(item)}
										alt={productTitle(item)}
										class="result-thumb"
										loading="lazy"
									/>
									<div class="result-info">
										<div class="result-title">{productTitle(item)}</div>
										<div class="result-meta">
											{#if item.category?.name}
												<span class="result-category">{item.category.name}</span>
											{/if}
											<span class="result-price">
												${Number(item.discount || item.price).toFixed(2)}
											</span>
										</div>
									</div>
									<i class="fa-solid fa-chevron-right result-arrow"></i>
								</a>
							{/each}
						</div>
						{#if total > results.length}
							<div class="dropdown-footer">
								<button
									type="button"
									class="btn-view-all"
									onclick={() => {
										const q = query.trim();
										closeSearch();
										goto(`/shop?search=${encodeURIComponent(q)}`);
									}}
								>
									{$translate('View all')} ({total}) <i class="fa-solid fa-arrow-right ms-1"></i>
								</button>
							</div>
						{/if}
					{/if}
				</div>
			{/if}
		</div>
	{/if}
</div>

<style>
	.navbar-search-wrapper {
		position: relative;
		display: inline-flex;
		align-items: center;
	}

	.search-trigger-btn {
		background: none;
		border: none;
		padding: 0;
		color: #1e2532;
		font-size: 18px;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 38px;
		height: 38px;
		border-radius: 50%;
		transition: background-color 0.2s, color 0.2s;
	}

	.search-trigger-btn:hover {
		background-color: #f1f3f9;
		color: #ef3e2e;
	}

	/* Açıq Axtarış Sətri */
	.search-bar-inline {
		position: relative;
		animation: searchSlide 0.2s ease-out forwards;
	}

	@keyframes searchSlide {
		from {
			opacity: 0;
			transform: scale(0.96) translateX(10px);
		}
		to {
			opacity: 1;
			transform: scale(1) translateX(0);
		}
	}

	.search-form {
		display: flex;
		align-items: center;
		gap: 7px;
		height: 38px;
		background: #eceef3;
		border: 1px solid transparent;
		border-radius: 10px;
		padding: 0 12px;
		width: 320px;
		box-shadow: none;
		transition: border-color 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
		overflow: hidden;
	}

	.search-form:focus-within {
		border-color: color-mix(in srgb, var(--theme) 42%, transparent);
		background: #f3f5f8;
		box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--theme) 18%, transparent);
	}

	.search-form-icon {
		color: #7b8190;
		font-size: 14px;
		flex-shrink: 0;
	}

	.search-input {
		min-height: 0 !important;
		height: 100%;
		border: 0 !important;
		border-radius: 0 !important;
		background: transparent !important;
		outline: 0 !important;
		box-shadow: none !important;
		font-size: 13px;
		color: #111827;
		width: 100%;
		min-width: 0;
		padding: 0 !important;
		line-height: 1;
	}

	.search-input:focus {
		border: 0 !important;
		outline: 0 !important;
		box-shadow: none !important;
	}

	.search-input::placeholder {
		color: #8b95a5;
		font-size: 13px;
	}

	.search-spinner {
		color: #8b95a5;
		font-size: 14px;
		margin-right: 8px;
		flex-shrink: 0;
	}

	.search-clear-btn {
		background: transparent;
		border: none;
		padding: 0;
		color: #8b95a5;
		font-size: 14px;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
	}

	.search-clear-btn:hover {
		color: #1e2532;
	}

	.search-close-btn {
		background: rgba(123, 129, 144, 0.14);
		border: none;
		width: 24px;
		height: 24px;
		border-radius: 50%;
		color: #556075;
		font-size: 13px;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
		transition: background-color 0.15s, color 0.15s;
	}

	.search-close-btn:hover {
		background-color: #ef3e2e;
		color: #ffffff;
	}

	/* Açılan Nəticələr Dropdown */
	.search-dropdown-menu {
		position: absolute;
		top: calc(100% + 10px);
		right: 0;
		width: 360px;
		background: #ffffff;
		border: 1px solid #edf0f5;
		border-radius: 14px;
		box-shadow: 0 14px 40px rgba(0, 0, 0, 0.12);
		z-index: 1050;
		overflow: hidden;
		animation: dropdownFade 0.18s ease-out;
	}

	@keyframes dropdownFade {
		from {
			opacity: 0;
			transform: translateY(-6px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	.dropdown-header {
		padding: 10px 16px;
		background: #f9fafc;
		border-bottom: 1px solid #edf0f5;
		font-size: 12px;
		font-weight: 600;
		color: #6c7588;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}

	.search-status-box {
		padding: 24px 16px;
		text-align: center;
		font-size: 14px;
		color: #6c7588;
	}

	.results-list {
		max-height: 360px;
		overflow-y: auto;
		display: flex;
		flex-direction: column;
	}

	.result-item {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 10px 16px;
		border-bottom: 1px solid #f4f6fa;
		text-decoration: none;
		transition: background-color 0.15s;
	}

	.result-item:last-child {
		border-bottom: none;
	}

	.result-item:hover {
		background-color: #f8f9fc;
	}

	.result-thumb {
		width: 44px;
		height: 44px;
		border-radius: 8px;
		background: #f4f6fa;
		object-fit: contain;
		padding: 3px;
		flex-shrink: 0;
	}

	.result-info {
		flex: 1;
		min-width: 0;
	}

	.result-title {
		font-size: 13.5px;
		font-weight: 600;
		color: #1e2532;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		line-height: 1.3;
	}

	.result-meta {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-top: 3px;
	}

	.result-category {
		font-size: 11.5px;
		color: #8b95a5;
		background: #edf0f5;
		padding: 1px 6px;
		border-radius: 4px;
	}

	.result-price {
		font-size: 13px;
		font-weight: 700;
		color: #ef3e2e;
	}

	.result-arrow {
		font-size: 12px;
		color: #c2c9d6;
		transition: transform 0.15s, color 0.15s;
		flex-shrink: 0;
	}

	.result-item:hover .result-arrow {
		transform: translateX(3px);
		color: #ef3e2e;
	}

	.dropdown-footer {
		padding: 8px 16px;
		background: #f9fafc;
		border-top: 1px solid #edf0f5;
		text-align: center;
	}

	.btn-view-all {
		background: transparent;
		border: none;
		color: #ef3e2e;
		font-size: 13px;
		font-weight: 600;
		cursor: pointer;
		padding: 4px 8px;
		display: inline-flex;
		align-items: center;
	}

	.btn-view-all:hover {
		text-decoration: underline;
	}

	@media (max-width: 575px) {
		.search-form {
			width: 240px;
			height: 34px;
			border-radius: 9px;
			padding: 0 10px;
		}

		.search-form-icon {
			font-size: 13px;
		}

		.search-input {
			font-size: 12px;
		}

		.search-input::placeholder {
			font-size: 12px;
		}

		.search-close-btn {
			width: 22px;
			height: 22px;
			font-size: 11px;
		}

		.search-dropdown-menu {
			width: 290px;
		}
	}
</style>
