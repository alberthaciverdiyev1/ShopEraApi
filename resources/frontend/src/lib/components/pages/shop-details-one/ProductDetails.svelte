<script lang="ts">
	import { onMount } from 'svelte';
	import { apiGet } from '$lib/utils/api';
	import { hasDiscount, fetchProducts, productImage, productTitle, type ApiProduct, type ApiProductFilter } from '$lib/services/products';
	import Rating from '$lib/components/ui/Rating.svelte';
	import ShopProductCard from '$lib/components/cards/ShopProductCard.svelte';
	import { addProductToCart } from '$lib/services/basket-actions';
	import { toggleFavoriteProduct } from '$lib/services/favorite-actions';
	import { fetchRecommendedProducts, subscribeToStock, unsubscribeFromStock } from '$lib/services/products';
	import { createReview, type ApiReview } from '$lib/services/reviews';
	import { features } from '$lib/services/features';
	import { isLoggedIn, user } from '$lib/services/auth';
	import { translate } from '$lib/i18n';

	let { product }: { product: ApiProduct } = $props();

	// Gallery images & Navigation
	const images = $derived.by(() => {
		const list = (product.images ?? [])
			.map((image) => image.image_path)
			.filter((src): src is string => !!src);
		if (list.length === 0) {
			const fallback = productImage(product);
			return fallback ? [fallback] : [];
		}
		return list;
	});

	let activeIndex = $state(0);
	const mainImage = $derived(images[activeIndex] ?? images[0] ?? productImage(product));

	// Hover zoom / lens state
	let isZooming = $state(false);
	let zoomOrigin = $state('50% 50%');

	function handleMouseMove(e: MouseEvent) {
		const target = e.currentTarget as HTMLElement;
		const rect = target.getBoundingClientRect();
		const x = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
		const y = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));
		zoomOrigin = `${x.toFixed(1)}% ${y.toFixed(1)}%`;
		isZooming = true;
	}

	function handleMouseLeave() {
		isZooming = false;
		zoomOrigin = '50% 50%';
	}

	// Touch swipe navigation for mobile
	let touchStartX = 0;
	let touchStartY = 0;

	function handleTouchStart(e: TouchEvent) {
		touchStartX = e.changedTouches[0].screenX;
		touchStartY = e.changedTouches[0].screenY;
	}

	function handleTouchEnd(e: TouchEvent) {
		const touchEndX = e.changedTouches[0].screenX;
		const touchEndY = e.changedTouches[0].screenY;
		const diffX = touchStartX - touchEndX;
		const diffY = touchStartY - touchEndY;
		if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY)) {
			if (diffX > 0) {
				nextImage();
			} else {
				prevImage();
			}
		}
	}

	function nextImage() {
		if (images.length <= 1) return;
		activeIndex = (activeIndex + 1) % images.length;
	}

	function prevImage() {
		if (images.length <= 1) return;
		activeIndex = (activeIndex - 1 + images.length) % images.length;
	}

	let thumbnailRowEl: HTMLElement | null = $state(null);

	function scrollThumbnails(direction: 'left' | 'right') {
		if (!thumbnailRowEl) return;
		const offset = direction === 'left' ? -160 : 160;
		thumbnailRowEl.scrollBy({ left: offset, behavior: 'smooth' });
	}

	async function openFullscreen(startIndex = activeIndex) {
		try {
			const { Fancybox } = await import('@fancyapps/ui');
			const galleryItems = images.map((src, i) => ({
				src,
				thumb: src,
				caption: `${productTitle(product)} (${i + 1}/${images.length})`
			}));

			const fancyboxOptions: any = {
				startIndex: Math.max(0, Math.min(startIndex, images.length - 1)),
				Thumbs: {
					autoStart: true
				},
				Toolbar: {
					display: {
						left: ['infobar'],
						middle: ['zoomIn', 'zoomOut', 'toggle1to1', 'rotateCCW', 'rotateCW', 'flipX', 'flipY'],
						right: ['slideshow', 'thumbs', 'close']
					}
				},
				on: {
					'Carousel.change': (_api: any, carousel: any) => {
						const page = carousel?.getPageIndex?.() ?? carousel?.page;
						if (typeof page === 'number') {
							activeIndex = page;
						}
					}
				}
			};

			Fancybox.show(galleryItems, fancyboxOptions);
		} catch (err) {
			console.error('Fancybox failed to open:', err);
		}
	}

	// State selections
	let quantity = $state(1);
	let adding = $state(false);
	let sizeId = $state<number | null>(product.sizes?.[0]?.id ?? null);
	let colorId = $state<number | null>(product.colors?.[0]?.id ?? null);
	let activeTab = $state<'description' | 'specs' | 'reviews'>('description');
	let isWishlisted = $state(Boolean(product.is_favorite));
	let addedToCart = $state(false);

	// Dynamic Filters (from product API or loaded via /product-filters)
	let dynamicFilters = $state<ApiProductFilter[]>(product.filters ?? []);

	// Similar products
	let subscribed = $state(Boolean(product.is_subscribe));
	let busySubscribe = $state(false);

	let similarProducts = $state<ApiProduct[]>([]);
	let similarLoading = $state(false);

	async function loadSimilar() {
		similarLoading = true;
		try {
			const categoryId = product.category?.id;
			const queryParams: Record<string, string | number | boolean | Array<string | number>> = { per_page: 8 };
			if (categoryId) {
				queryParams.category_ids = [categoryId];
			}
			const list = await fetchProducts(queryParams);
			const filtered = list.filter((p) => p.id !== product.id);
			if (filtered.length >= 4) {
				similarProducts = filtered.slice(0, 4);
			} else {
				// `GET /product/recommend` personalises from the shopper's orders.
				const fallback = await fetchRecommendedProducts(8);
				const extra = fallback.filter(
					(p) => p.id !== product.id && !filtered.some((f) => f.id === p.id)
				);
				similarProducts = [...filtered, ...extra].slice(0, 4);
			}
		} catch (err) {
			console.error('Similar products loading error:', err);
		} finally {
			similarLoading = false;
		}
	}

	onMount(async () => {
		loadSimilar();

		if (!dynamicFilters.length && product.id) {
			try {
				const res = await apiGet<ApiProductFilter[]>('/product-filters', { product_id: product.id });
				if (Array.isArray(res) && res.length) {
					dynamicFilters = res;
				}
			} catch {
				// Silently fallback if filter endpoint is unavailable
			}
		}
	});

	const selectedColor = $derived(product.colors?.find((c) => c.id === colorId));
	const selectedSize = $derived(product.sizes?.find((s) => s.id === sizeId));

	// Pricing calculation
	const originalPrice = $derived(Number(product.price || 0));
	const discountedPrice = $derived(Number(product.discount || 0));
	const currentPrice = $derived(hasDiscount(product) ? discountedPrice : originalPrice);
	const discountPercent = $derived(
		hasDiscount(product) && originalPrice > 0
			? Math.round(((originalPrice - discountedPrice) / originalPrice) * 100)
			: 0
	);

	// Reviews
	let extraReviews = $state<ApiReview[]>([]);
	let reviews = $derived(product.reviews ? [...(product.reviews as ApiReview[]), ...extraReviews] : extraReviews);
	const reviewCount = $derived(reviews.length || (product.rate_count ?? 0));

	let newRate = $state(5);
	let newComment = $state('');
	let sendingReview = $state(false);
	let reviewMessage = $state<string | null>(null);
	let reviewError = $state<string | null>(null);

	async function submitReview(event: SubmitEvent) {
		event.preventDefault();
		sendingReview = true;
		reviewMessage = reviewError = null;

		try {
			await createReview(product.id, newRate, newComment);
			extraReviews = [
				{
					id: Date.now(),
					rate: newRate,
					comment: newComment,
					created_at: new Date().toLocaleDateString('az-AZ'),
					user: { name: $user?.name ?? 'Siz' }
				},
				...extraReviews
			];
			newComment = '';
			newRate = 5;
			reviewMessage = 'Rəyiniz üçün təşəkkürlər!';
		} catch (e) {
			reviewError = e instanceof Error ? e.message : 'Rəy göndərilə bilmədi';
		} finally {
			sendingReview = false;
		}
	}
	const productCode = $derived(product.sku || String(product.id).padStart(6, '0'));

	const CORE_KEYS = ['brend', 'brand', 'kateqoriya', 'category', 'üslub', 'uslub', 'style', 'məqsəd', 'meqsed', 'purpose'];

	// Top Parameter Box (Specifications) — purely dynamic from dynamic filters / specifications!
	const specs = $derived.by(() => {
		if (dynamicFilters.length) {
			const paramFilters = dynamicFilters.filter(
				(f) => !CORE_KEYS.includes((f.name || f.title || '').trim().toLowerCase())
			);
			if (paramFilters.length) {
				return paramFilters.map((f) => ({
					label: f.name || f.title || 'Parametr',
					value: String(f.value)
				}));
			}
		}

		if (product.specifications && Object.keys(product.specifications).length) {
			return Object.entries(product.specifications).map(([label, value]) => ({
				label,
				value: String(value)
			}));
		}

		return [];
	});

	// Core Characteristics (Əsas xarakteristikalar) — purely dynamic!
	const characteristics = $derived.by(() => {
		const getFilterVal = (keys: string[]) => {
			const found = dynamicFilters.find((f) =>
				keys.includes((f.name || f.title || '').trim().toLowerCase())
			);
			return found ? String(found.value) : null;
		};

		const list: Array<{ label: string; value: string; link?: string }> = [];

		const brandName = product.brand?.name ?? getFilterVal(['brend', 'brand']);
		if (brandName) {
			list.push({
				label: 'Brend',
				value: brandName,
				link: product.brand?.id ? `/shop?brand=${product.brand.id}` : undefined
			});
		}

		const categoryName = product.category?.name ?? getFilterVal(['kateqoriya', 'category']);
		if (categoryName) {
			list.push({
				label: 'Kateqoriya',
				value: categoryName,
				link: product.category?.id ? `/shop?category=${product.category.id}` : undefined
			});
		}

		const styleVal = getFilterVal(['üslub', 'uslub', 'style']) ?? product.gender;
		if (styleVal) {
			list.push({
				label: 'Üslub',
				value: styleVal
			});
		}

		const purposeVal = getFilterVal(['məqsəd', 'meqsed', 'purpose']);
		if (purposeVal) {
			list.push({
				label: 'Məqsəd',
				value: purposeVal
			});
		}

		return list;
	});

	function formatPrice(val: number): string {
		return val.toFixed(2);
	}

	async function handleAddToCart() {
		adding = true;
		try {
			await addProductToCart(product.id, quantity, sizeId, colorId);
			addedToCart = true;
			setTimeout(() => {
				addedToCart = false;
			}, 2500);
		} catch (e) {
			console.error('Failed to add to cart:', e);
		} finally {
			adding = false;
		}
	}

	async function toggleWishlist() {
		try {
			await toggleFavoriteProduct(product.id);
			isWishlisted = !isWishlisted;
		} catch {
			// leave the flag untouched if the request failed
		}
	}

	const outOfStock = $derived(Number(product.stock_count ?? 0) <= 0);

	async function toggleSubscription() {
		busySubscribe = true;
		try {
			if (subscribed) {
				await unsubscribeFromStock(product.id);
			} else {
				await subscribeToStock(product.id);
			}
			subscribed = !subscribed;
		} finally {
			busySubscribe = false;
		}
	}

</script>

<div class="product-details-container">
	<div class="container">
		<div class="row gx-5 gy-4">
			<!-- Sol Tərəf: Şəkil Qalereyası -->
			<div class="col-lg-6">
				<div class="gallery-wrapper">
					<!-- Əsas Şəkil Kartı (Tam Ekran, Zoom və Swipe Dəstəyi ilə) -->
					<div
						class="main-image-card"
						class:zooming={isZooming}
						style="--zoom-origin: {zoomOrigin};"
						onmousemove={handleMouseMove}
						onmouseleave={handleMouseLeave}
						ontouchstart={handleTouchStart}
						ontouchend={handleTouchEnd}
						onclick={() => openFullscreen(activeIndex)}
						role="button"
						tabindex="0"
						onkeydown={(e) => (e.key === 'Enter' || e.key === ' ') && openFullscreen(activeIndex)}
						aria-label={$translate('View fullscreen image')}
					>
						<!-- Nişanlar: Endirim və Yenilik -->
						<div class="gallery-badges">
							{#if discountPercent > 0}
								<span class="gallery-badge discount">-{discountPercent}%</span>
							{/if}
							{#if product.is_new}
								<span class="gallery-badge new">{$translate('New')}</span>
							{/if}
						</div>

						<!-- Yuxarı sağ fəaliyyətlər: Sevimli və Tam Ekran -->
						<div class="gallery-top-actions">
							<button
								type="button"
								class="gallery-action-btn wishlist-action-btn"
								class:active={isWishlisted}
								onclick={(e) => {
									e.stopPropagation();
									toggleWishlist();
								}}
								aria-label={$translate('Add to wishlist')}
								title={$translate('Wishlist')}
							>
								<i class="fa-{isWishlisted ? 'solid text-danger' : 'regular'} fa-heart"></i>
							</button>

							<button
								type="button"
								class="gallery-action-btn fullscreen-btn"
								onclick={(e) => {
									e.stopPropagation();
									openFullscreen(activeIndex);
								}}
								aria-label={$translate('View full screen')}
								title={$translate('Full Screen')}
							>
								<i class="fa-solid fa-expand"></i>
							</button>
						</div>

						<!-- Əsas Şəkil -->
						<img
							src={mainImage}
							alt={productTitle(product)}
							class="main-image"
							loading="eager"
						/>

						<!-- Şəkillər arası keçid oxları (birdən çox olduqda) -->
						{#if images.length > 1}
							<button
								type="button"
								class="gallery-nav-btn prev-btn"
								onclick={(e) => {
									e.stopPropagation();
									prevImage();
								}}
								aria-label={$translate('Previous image')}
								title={$translate('Previous')}
							>
								<i class="fa-solid fa-chevron-left"></i>
							</button>

							<button
								type="button"
								class="gallery-nav-btn next-btn"
								onclick={(e) => {
									e.stopPropagation();
									nextImage();
								}}
								aria-label={$translate('Next image')}
								title={$translate('Next')}
							>
								<i class="fa-solid fa-chevron-right"></i>
							</button>

							<!-- Şəkil sayğacı -->
							<div class="gallery-counter">
								<i class="fa-regular fa-image me-1"></i>
								<span>{activeIndex + 1} / {images.length}</span>
							</div>
						{/if}

						<!-- Hover zamanı klikləmə ipucu -->
						<div class="gallery-hint">
							<i class="fa-solid fa-magnifying-glass-plus me-1"></i>
							<span>{$translate('Click to enlarge')}</span>
						</div>
					</div>

					<!-- Miniatürlər Zolağı -->
					{#if images.length > 1}
						<div class="thumbnail-bar-wrap">
							<button
								type="button"
								class="thumb-scroll-btn left"
								onclick={() => scrollThumbnails('left')}
								aria-label={$translate('Scroll thumbnails left')}
							>
								<i class="fa-solid fa-chevron-left"></i>
							</button>

							<div class="thumbnail-row" bind:this={thumbnailRowEl}>
								{#each images as img, index (index)}
									<button
										type="button"
										class="thumb-btn"
										class:active={index === activeIndex}
										onclick={() => (activeIndex = index)}
										aria-label={`${$translate('View')} ${index + 1}`}
									>
										<img src={img} alt="" loading="lazy" />
									</button>
								{/each}
							</div>

							<button
								type="button"
								class="thumb-scroll-btn right"
								onclick={() => scrollThumbnails('right')}
								aria-label={$translate('Scroll thumbnails right')}
							>
								<i class="fa-solid fa-chevron-right"></i>
							</button>
						</div>
					{/if}
				</div>
			</div>

			<!-- Sağ Tərəf: Məhsul Məlumatları və Fəaliyyətlər -->
			<div class="col-lg-6">
				<div class="details-content">
					<!-- Başlıq -->
					<h1 class="product-title">{productTitle(product)}</h1>

					<!-- Məhsul Kodu Nişanı -->
					<div class="code-badge-wrap">
						<span class="code-badge">{$translate('Product code')}: {productCode}</span>
					</div>

					<!-- Spesifikasiyalar Qutusu (3-lü Grid — Yalnız Dinamik Filtrlər Olduqda) -->
					{#if specs.length}
						<div class="specs-grid-box">
							<div class="specs-grid">
								{#each specs as item}
									<div class="spec-col">
										<div class="spec-label">{item.label}</div>
										<div class="spec-val">{item.value}</div>
									</div>
								{/each}
							</div>
						</div>
					{/if}

					<!-- Əsas Xarakteristikalar -->
					{#if characteristics.length}
						<div class="characteristics-section">
							<h3 class="char-title">{$translate('Key characteristics')}</h3>
							<div class="chars-grid">
								{#each characteristics as char}
									<div class="char-col">
										<div class="char-label">{char.label}</div>
										{#if char.link}
											<a href={char.link} class="char-val char-link">{char.value}</a>
										{:else}
											<div class="char-val">{char.value}</div>
										{/if}
									</div>
								{/each}
							</div>
						</div>
					{/if}

					<!-- Rəng və Ölçü Seçimi (Mövcudsa) -->
					{#if product.colors?.length}
						<div class="option-group">
							<span class="option-title">{$translate('Color')}:</span>
							<div class="color-swatches">
								{#each product.colors as color (color.id)}
									<button
										type="button"
										class="color-chip"
										class:active={colorId === color.id}
										onclick={() => (colorId = color.id)}
										title={color.name ?? ''}
										aria-label={color.name ?? $translate('Color')}
									>
										<span class="swatch-dot" style={`background-color: ${color.hex ?? '#ccc'}`}></span>
										{#if color.name}<span class="color-text">{color.name}</span>{/if}
									</button>
								{/each}
							</div>
						</div>
					{/if}

					{#if product.sizes?.length}
						<div class="option-group">
							<span class="option-title">{$translate('Size')}:</span>
							<div class="size-swatches">
								{#each product.sizes as size (size.id)}
									<button
										type="button"
										class="size-chip"
										class:active={sizeId === size.id}
										onclick={() => (sizeId = size.id)}
									>
										{size.name}
									</button>
								{/each}
							</div>
						</div>
					{/if}

					<!-- Qiymət və Səbət Fəaliyyət Kartı -->
					<div class="action-card">
						<div class="price-row">
							<div class="price-display">
								<span class="main-price">{formatPrice(currentPrice)} ₼</span>
								{#if hasDiscount(product)}
									<span class="old-price">{formatPrice(originalPrice)} ₼</span>
									<span class="discount-pill">-{discountPercent}%</span>
								{/if}
							</div>

							<div class="qty-control">
								<button
									type="button"
									class="qty-btn"
									onclick={() => (quantity = Math.max(1, quantity - 1))}
									aria-label={$translate('Decrease')}
								>
									<i class="fa-solid fa-minus"></i>
								</button>
								<span class="qty-val" aria-label={$translate('Quantity')}>{quantity}</span>
								<button
									type="button"
									class="qty-btn"
									onclick={() => (quantity = quantity + 1)}
									aria-label={$translate('Increase')}
								>
									<i class="fa-solid fa-plus"></i>
								</button>
							</div>
						</div>

						<button
							type="button"
							class="add-to-cart-btn"
							class:added={addedToCart}
							onclick={handleAddToCart}
						>
							{#if addedToCart}
								<i class="fa-solid fa-check"></i>
								<span>{$translate('Added to cart!')}</span>
							{:else}
								<i class="fa-solid fa-cart-shopping"></i>
								<span>{$translate('Add to cart')}</span>
							{/if}
						</button>

						<div class="sub-actions">
							<button
								type="button"
								class="sub-action-btn"
								class:active={isWishlisted}
								onclick={toggleWishlist}
							>
								<i class={isWishlisted ? 'fa-solid fa-heart' : 'fa-regular fa-heart'}></i>
								<span>{isWishlisted ? $translate('Remove favorite') : $translate('Add to wishlist')}</span>
							</button>

							{#if outOfStock}
								<button
									type="button"
									class="sub-action-btn"
									class:active={subscribed}
									disabled={busySubscribe}
									onclick={toggleSubscription}
								>
									<i class="fa-regular fa-bell"></i>
									<span>{subscribed ? $translate('Stock alert is on') : $translate('Notify me when in stock')}</span>
								</button>
							{/if}
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Alt Bölmə: Təsvir, Bütün Parametrlər və Rəylər Tabları -->
		<div class="row mt-5">
			<div class="col-12">
				<div class="details-tabs-wrapper">
					<div class="tabs-nav">
						<button
							type="button"
							class="tab-btn"
							class:active={activeTab === 'description'}
							onclick={() => (activeTab = 'description')}
						>
							{$translate('About product')}
						</button>
						<button
							type="button"
							class="tab-btn"
							class:active={activeTab === 'specs'}
							onclick={() => (activeTab = 'specs')}
						>
							{$translate('All specifications')}
						</button>
						{#if $features.reviews}
							<button
							type="button"
							class="tab-btn"
							class:active={activeTab === 'reviews'}
							onclick={() => (activeTab = 'reviews')}
						>
							{$translate('Reviews')} ({reviewCount})
						</button>
							{/if}
					</div>

					<div class="tab-content-panel">
						{#if activeTab === 'description'}
							<div class="description-body">
								<p>{product.description || $translate('No additional description is available for this product.')}</p>
							</div>
						{:else if activeTab === 'specs'}
							<div class="specs-table-body">
								{#if specs.length || characteristics.length || product.weight}
									<table class="table specs-table">
										<tbody>
											{#each specs as s}
												<tr>
													<td class="table-label">{s.label}</td>
													<td class="table-val">{s.value}</td>
												</tr>
											{/each}
											{#each characteristics as c}
												<tr>
													<td class="table-label">{c.label}</td>
													<td class="table-val">{c.value}</td>
												</tr>
											{/each}
											{#if product.weight}
												<tr>
													<td class="table-label">{$translate('Weight')}</td>
													<td class="table-val">{product.weight} kq</td>
												</tr>
											{/if}
											<tr>
												<td class="table-label">{$translate('Product code')}</td>
												<td class="table-val">{productCode}</td>
											</tr>
										</tbody>
									</table>
								{:else}
									<p class="text-muted">{$translate('No additional specifications are listed for this product.')}</p>
								{/if}
							</div>
						{:else if activeTab === 'reviews'}
							<div class="reviews-body">
								<div class="review-header">
									<div class="score-box">
										<span class="score-num">{Number(product.rate ?? 0).toFixed(1)}</span>
										<Rating value={product.rate ?? 0} size="md" />
										<span class="score-count">({reviewCount} {$translate('Reviews')})</span>
									</div>
								</div>

								{#if reviews.length}
									<div class="review-items">
										{#each reviews as review (review.id)}
											<div class="review-entry">
												<div class="review-avatar">
													{(review.user?.name ?? 'M').trim().charAt(0).toUpperCase()}
												</div>
												<div class="review-content">
													<div class="review-meta">
														<span class="user-name">{review.user?.name ?? $translate('Customer')}</span>
														<span class="review-date">{review.created_at ?? ''}</span>
													</div>
													<Rating value={review.rate ?? 0} size="sm" />
													<p class="review-text">{review.comment ?? ''}</p>
												</div>
											</div>
										{/each}
									</div>
								{:else}
									<p class="no-reviews">{$translate('No reviews yet. Be the first to write one!')}</p>
								{/if}

								<!-- Rəy yazma formu -->
								<div class="review-form">
									<h4>{$translate('Write a review')}</h4>
									{#if !$isLoggedIn}
										<p class="review-hint">
											{$translate('Please')} <a href="/login">{$translate('Login')}</a> {$translate('to write a review.')}
										</p>
									{:else}
										<form onsubmit={submitReview}>
											<div class="rate-picker">
												<span>{$translate('Rating')}:</span>
												{#each Array(5) as _, index (index)}
													<button type="button" class="star-btn"
															aria-label={$translate('{count} stars', { count: index + 1 })}
															onclick={() => (newRate = index + 1)}>
														<i class="fa-{index < newRate ? 'solid' : 'regular'} fa-star"></i>
													</button>
												{/each}
											</div>
											<textarea rows="4" placeholder={$translate('Write your thoughts...')} bind:value={newComment}></textarea>
											{#if reviewMessage}<p class="review-ok">{reviewMessage}</p>{/if}
											{#if reviewError}<p class="review-err">{reviewError}</p>{/if}
											<button type="submit" class="submit-review" disabled={sendingReview}>
												{sendingReview ? $translate('Sending...') : $translate('Submit review')}
											</button>
										</form>
									{/if}
								</div>
							</div>
						{/if}
					</div>
				</div>
			</div>
		</div>

		<!-- Oxşar Məhsullar (Similar Products) -->
		{#if similarProducts.length}
			<div class="row mt-5 pt-4">
				<div class="col-12">
					<div class="similar-products-header">
						<div>
							<h3 class="similar-title">{$translate('Oxşar məhsullar')}</h3>
							<p class="similar-subtitle">{$translate('Bəyənə biləcəyiniz digər oxşar məhsullar')}</p>
						</div>
						{#if product.category?.id}
							<a href={`/shop?category=${product.category.id}`} class="view-all-link">
								{$translate('Hamısına bax')} <i class="fa-solid fa-arrow-right ms-1"></i>
							</a>
						{/if}
					</div>

					<div class="row g-4 mt-1 similar-products-grid">
						{#each similarProducts as similar (similar.id)}
							<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-6">
								<ShopProductCard product={similar} />
							</div>
						{/each}
					</div>
				</div>
			</div>
		{/if}
	</div>
</div>

<style>
	.product-details-container {
		padding: 40px 0 80px;
		background: #ffffff;
		font-family: inherit;
		color: #2b3445;
	}

	/* Oxşar Məhsullar */
	.similar-products-header {
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		padding-bottom: 16px;
		border-bottom: 2px solid #edf0f5;
		margin-bottom: 24px;
	}

	.similar-title {
		font-size: 22px;
		font-weight: 700;
		color: #1e2532;
		margin: 0 0 4px;
	}

	.similar-subtitle {
		font-size: 14px;
		color: #6c7588;
		margin: 0;
	}

	.view-all-link {
		font-size: 14px;
		font-weight: 600;
		color: var(--theme);
		text-decoration: none;
		transition: color 0.15s ease;
		display: inline-flex;
		align-items: center;
	}

	.view-all-link:hover {
		color: color-mix(in srgb, var(--theme) 86%, #000);
		text-decoration: underline;
	}

	/* Qalereya */
	.gallery-wrapper {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 16px;
		width: 100%;
	}

	.main-image-card {
		position: relative;
		width: 100%;
		max-width: 540px;
		height: 480px;
		background: #ffffff;
		border: 1px solid #edf2f7;
		border-radius: 20px;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 24px;
		overflow: hidden;
		box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04);
		cursor: zoom-in;
		user-select: none;
		transition: border-color 0.2s, box-shadow 0.2s;
	}

	.main-image-card:hover {
		border-color: #cbd5e1;
		box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
	}

	.main-image {
		max-width: 100%;
		max-height: 100%;
		object-fit: contain;
		transform-origin: var(--zoom-origin, 50% 50%);
		transition: transform 0.15s ease-out;
		pointer-events: none;
	}

	.main-image-card.zooming .main-image {
		transform: scale(2);
	}

	/* Top badges */
	.gallery-badges {
		position: absolute;
		top: 16px;
		left: 16px;
		display: flex;
		flex-direction: column;
		gap: 6px;
		z-index: 3;
		pointer-events: none;
	}

	.gallery-badge {
		padding: 4px 10px;
		border-radius: 999px;
		font-size: 12px;
		font-weight: 700;
		line-height: 1.2;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
	}

	.gallery-badge.discount {
		background: #ef4444;
		color: #ffffff;
	}

	.gallery-badge.new {
		background: #0ea5e9;
		color: #ffffff;
	}

	/* Top Right Actions (Wishlist & Fullscreen) */
	.gallery-top-actions {
		position: absolute;
		top: 16px;
		right: 16px;
		display: flex;
		gap: 8px;
		z-index: 3;
	}

	.gallery-action-btn {
		width: 38px;
		height: 38px;
		border-radius: 50%;
		border: 1px solid #e2e8f0;
		background: rgba(255, 255, 255, 0.92);
		backdrop-filter: blur(8px);
		color: #475569;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 15px;
		cursor: pointer;
		box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
		transition: all 0.2s ease;
	}

	.gallery-action-btn:hover {
		background: #ffffff;
		color: var(--theme);
		transform: scale(1.08);
		border-color: #cbd5e1;
		box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
	}

	.gallery-action-btn.active {
		color: #ef4444;
	}

	/* Navigation arrows */
	.gallery-nav-btn {
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.9);
		backdrop-filter: blur(6px);
		border: 1px solid #e2e8f0;
		color: #334155;
		font-size: 14px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		z-index: 3;
		opacity: 0;
		transition: opacity 0.2s ease, transform 0.2s ease, background 0.2s ease;
		box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
	}

	.main-image-card:hover .gallery-nav-btn {
		opacity: 1;
	}

	.gallery-nav-btn.prev-btn {
		left: 14px;
	}

	.gallery-nav-btn.next-btn {
		right: 14px;
	}

	.gallery-nav-btn:hover {
		background: #ffffff;
		color: var(--theme);
		transform: translateY(-50%) scale(1.1);
	}

	/* Gallery counter badge */
	.gallery-counter {
		position: absolute;
		bottom: 16px;
		right: 16px;
		padding: 4px 10px;
		border-radius: 999px;
		background: rgba(15, 23, 42, 0.65);
		backdrop-filter: blur(6px);
		color: #ffffff;
		font-size: 12px;
		font-weight: 600;
		display: flex;
		align-items: center;
		z-index: 3;
		pointer-events: none;
	}

	/* Hint overlay on hover */
	.gallery-hint {
		position: absolute;
		bottom: 16px;
		left: 16px;
		padding: 4px 10px;
		border-radius: 999px;
		background: rgba(15, 23, 42, 0.65);
		backdrop-filter: blur(6px);
		color: #ffffff;
		font-size: 11.5px;
		font-weight: 500;
		display: flex;
		align-items: center;
		opacity: 0;
		transform: translateY(4px);
		transition: opacity 0.2s, transform 0.2s;
		z-index: 3;
		pointer-events: none;
	}

	.main-image-card:hover .gallery-hint {
		opacity: 1;
		transform: translateY(0);
	}

	/* Thumbnail bar wrap */
	.thumbnail-bar-wrap {
		width: 100%;
		max-width: 540px;
		display: flex;
		align-items: center;
		gap: 8px;
		position: relative;
	}

	.thumb-scroll-btn {
		flex: 0 0 28px;
		height: 60px;
		border: 1px solid #e2e8f0;
		background: #ffffff;
		border-radius: 8px;
		color: #64748b;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 12px;
		cursor: pointer;
		transition: all 0.15s ease;
	}

	.thumb-scroll-btn:hover {
		background: #f8fafc;
		color: var(--theme);
		border-color: #cbd5e1;
	}

	.thumbnail-row {
		flex: 1;
		display: flex;
		gap: 10px;
		justify-content: flex-start;
		flex-wrap: nowrap;
		overflow-x: auto;
		overflow-y: hidden;
		padding: 4px 2px 8px;
		scrollbar-width: thin;
		scroll-snap-type: x proximity;
		-webkit-overflow-scrolling: touch;
	}

	.thumbnail-row::-webkit-scrollbar {
		height: 4px;
	}

	.thumbnail-row::-webkit-scrollbar-thumb {
		border-radius: 999px;
		background: #cbd5e1;
	}

	.thumb-btn {
		flex: 0 0 auto;
		width: 70px;
		height: 70px;
		border-radius: 12px;
		border: 2px solid transparent;
		background: #ffffff;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
		padding: 6px;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		scroll-snap-align: start;
		transition: all 0.2s ease;
		opacity: 0.7;
	}

	.thumb-btn:hover {
		opacity: 1;
		border-color: #cbd5e1;
		transform: translateY(-2px);
	}

	.thumb-btn.active {
		opacity: 1;
		border-color: var(--theme);
		box-shadow: 0 0 0 2px color-mix(in srgb, var(--theme) 20%, transparent);
		transform: translateY(-2px);
	}

	.thumb-btn img {
		max-width: 100%;
		max-height: 100%;
		object-fit: contain;
	}

	/* Məhsul Məlumatları */
	.details-content {
		display: flex;
		flex-direction: column;
	}

	.product-title {
		font-size: 28px;
		font-weight: 600;
		color: #1e2532;
		margin: 0 0 10px;
		line-height: 1.3;
	}

	.code-badge-wrap {
		margin-bottom: 22px;
	}

	.code-badge {
		display: inline-block;
		background: #edf0f5;
		color: #556075;
		font-size: 13px;
		font-weight: 500;
		padding: 4px 12px;
		border-radius: 6px;
	}

	/* Spesifikasiyalar Qutusu (Boz/Lavant Grid) */
	.specs-grid-box {
		background: #f1f3f9;
		border-radius: 12px;
		padding: 22px 24px;
		margin-bottom: 24px;
	}

	.specs-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 18px 24px;
	}

	.spec-col {
		display: flex;
		flex-direction: column;
	}

	.spec-label {
		font-size: 13px;
		color: #6c7588;
		margin-bottom: 4px;
	}

	.spec-val {
		font-size: 14px;
		font-weight: 600;
		color: var(--theme);
	}

	/* Əsas Xarakteristikalar */
	.characteristics-section {
		margin-bottom: 24px;
	}

	.char-title {
		font-size: 16px;
		font-weight: 700;
		color: #1e2532;
		margin-bottom: 14px;
	}

	.chars-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 16px 20px;
	}

	.char-col {
		display: flex;
		flex-direction: column;
	}

	.char-label {
		font-size: 13px;
		color: #6c7588;
		margin-bottom: 4px;
	}

	.char-val {
		font-size: 14px;
		font-weight: 600;
		color: var(--theme);
		text-decoration: none;
	}

	.char-link:hover {
		text-decoration: underline;
	}

	/* Variant Seçimləri */
	.option-group {
		margin-bottom: 20px;
	}

	.option-title {
		font-size: 13px;
		font-weight: 600;
		color: #1e2532;
		display: block;
		margin-bottom: 8px;
	}

	.color-swatches,
	.size-swatches {
		display: flex;
		gap: 8px;
		flex-wrap: wrap;
	}

	.color-chip {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 5px 12px;
		background: #f7f8fa;
		border: 1px solid #e2e6ec;
		border-radius: 6px;
		cursor: pointer;
		font-size: 13px;
		color: #333;
		transition: all 0.2s;
	}

	.color-chip.active {
		border-color: var(--theme);
		background: #fff;
		box-shadow: 0 0 0 1px var(--theme);
	}

	.swatch-dot {
		width: 14px;
		height: 14px;
		border-radius: 50%;
		border: 1px solid rgba(0, 0, 0, 0.1);
	}

	.size-chip {
		min-width: 40px;
		padding: 6px 14px;
		background: #f7f8fa;
		border: 1px solid #e2e6ec;
		border-radius: 6px;
		cursor: pointer;
		font-size: 13px;
		font-weight: 500;
		color: #333;
		transition: all 0.2s;
	}

	.size-chip.active {
		border-color: var(--theme);
		background: #fff;
		color: var(--theme);
		font-weight: 700;
		box-shadow: 0 0 0 1px var(--theme);
	}

	/* Qiymət və Səbət Kartı */
	.action-card {
		background: #f1f3f9;
		border-radius: 14px;
		padding: 24px;
		margin-top: 8px;
	}

	.price-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 18px;
	}

	.price-display {
		display: flex;
		align-items: baseline;
		gap: 12px;
	}

	.main-price {
		font-size: 32px;
		font-weight: 700;
		color: #1e2532;
		line-height: 1;
	}

	.old-price {
		font-size: 16px;
		color: #8b95a5;
		text-decoration: line-through;
	}

	.discount-pill {
		font-size: 12px;
		font-weight: 700;
		color: var(--theme);
		background: color-mix(in srgb, var(--theme) 12%, transparent);
		padding: 2px 8px;
		border-radius: 4px;
	}

	.qty-control {
		display: inline-flex;
		align-items: center;
		background: #ffffff;
		border: 1px solid #dde1ea;
		border-radius: 8px;
		overflow: hidden;
		height: 38px;
	}

	.qty-btn {
		width: 34px;
		height: 100%;
		border: none;
		background: transparent;
		color: #556075;
		font-size: 12px;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: background 0.15s;
	}

	.qty-btn:hover {
		background: #f0f2f7;
		color: var(--theme);
	}

	.qty-val {
		width: 40px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		font-size: 14px;
		font-weight: 600;
		color: #1e2532;
		line-height: 1;
	}

	.add-to-cart-btn {
		width: 100%;
		background: var(--theme);
		color: #ffffff;
		border: none;
		border-radius: 8px;
		padding: 14px 20px;
		font-size: 15px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.5px;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 10px;
		cursor: pointer;
		transition: background 0.2s ease, transform 0.1s ease;
	}

	.add-to-cart-btn:hover {
		background: color-mix(in srgb, var(--theme) 86%, #000);
	}

	.add-to-cart-btn:active {
		transform: scale(0.99);
	}

	.add-to-cart-btn.added {
		background: #28a745;
	}

	.sub-actions {
		display: flex;
		gap: 24px;
		margin-top: 16px;
	}

	.sub-action-btn {
		background: transparent;
		border: none;
		padding: 0;
		display: inline-flex;
		align-items: center;
		gap: 8px;
		font-size: 14px;
		color: #556075;
		cursor: pointer;
		transition: color 0.15s ease;
	}

	.sub-action-btn:hover,
	.sub-action-btn.active {
		color: var(--theme);
	}

	.sub-action-btn i {
		font-size: 16px;
		color: var(--theme);
	}

	/* Tablar və Alt Məzmun */
	.details-tabs-wrapper {
		border-top: 1px solid #edf0f5;
		padding-top: 36px;
	}

	.tabs-nav {
		display: flex;
		gap: 12px;
		border-bottom: 2px solid #edf0f5;
		margin-bottom: 24px;
	}

	.tab-btn {
		background: transparent;
		border: none;
		padding: 10px 18px;
		font-size: 16px;
		font-weight: 600;
		color: #6c7588;
		border-bottom: 2px solid transparent;
		margin-bottom: -2px;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.tab-btn:hover {
		color: var(--theme);
	}

	.tab-btn.active {
		color: var(--theme);
		border-bottom-color: var(--theme);
	}

	.tab-content-panel {
		padding: 10px 0;
	}

	.description-body {
		font-size: 15px;
		line-height: 1.8;
		color: #4a5568;
	}

	.specs-table-body {
		width: 100%;
		overflow-x: auto;
	}

	.specs-table {
		width: 100%;
		max-width: 100%;
		margin-bottom: 0;
		border-collapse: separate;
		border-spacing: 0;
		border: 1px solid #edf0f5;
		border-radius: 12px;
		overflow: hidden;
	}

	.specs-table tr {
		transition: background-color 0.15s ease;
	}

	.specs-table tr:nth-child(even) {
		background-color: #f9fafc;
	}

	.specs-table tr:hover {
		background-color: #f1f4f9;
	}

	.specs-table td {
		padding: 16px 24px;
		border-top: 1px solid #edf0f5;
		font-size: 15px;
		vertical-align: middle;
	}

	.specs-table tr:first-child td {
		border-top: none;
	}

	.table-label {
		color: #6c7588;
		font-weight: 500;
		width: 50%;
		min-width: 180px;
		text-align: left;
	}

	.table-val {
		color: #1e2532;
		font-weight: 600;
		width: 50%;
		text-align: right;
	}

	.review-form {
		margin-top: 26px;
		border-top: 1px solid #eef0f4;
		padding-top: 20px;
	}

	.review-form h4 {
		margin-bottom: 12px;
	}

	.rate-picker {
		display: flex;
		align-items: center;
		gap: 6px;
		margin-bottom: 12px;
	}

	.star-btn {
		background: none;
		border: 0;
		padding: 0 2px;
		cursor: pointer;
		color: #ff8a00;
		font-size: 18px;
	}

	.review-form textarea {
		width: 100%;
		border: 1px solid #e2e6ec;
		border-radius: 10px;
		padding: 12px;
		margin-bottom: 12px;
	}

	.submit-review {
		background: var(--theme);
		color: #fff;
		border: 0;
		border-radius: 10px;
		padding: 11px 22px;
		cursor: pointer;
	}

	.submit-review:disabled {
		opacity: 0.6;
	}

	.review-ok {
		color: #1a7f37;
		font-size: 14px;
	}

	.review-err {
		color: #e2453c;
		font-size: 14px;
	}

	.review-hint a {
		color: var(--theme);
	}

	/* Rəylər */
	.review-header {
		display: flex;
		align-items: center;
		gap: 16px;
		margin-bottom: 24px;
	}

	.score-box {
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.score-num {
		font-size: 26px;
		font-weight: 700;
		color: #1e2532;
	}

	.score-count {
		font-size: 14px;
		color: #6c7588;
	}

	.review-items {
		display: flex;
		flex-direction: column;
		gap: 14px;
	}

	.review-entry {
		display: flex;
		gap: 16px;
		padding: 16px;
		border: 1px solid #edf0f5;
		border-radius: 12px;
		background: #ffffff;
	}

	.review-avatar {
		width: 42px;
		height: 42px;
		border-radius: 50%;
		background: var(--theme);
		color: #ffffff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: 700;
		font-size: 16px;
		flex-shrink: 0;
	}

	.review-content {
		flex: 1;
	}

	.review-meta {
		display: flex;
		justify-content: space-between;
		margin-bottom: 4px;
	}

	.user-name {
		font-size: 14px;
		font-weight: 600;
		color: #1e2532;
	}

	.review-date {
		font-size: 12px;
		color: #8b95a5;
	}

	.review-text {
		font-size: 14px;
		color: #4a5568;
		line-height: 1.6;
		margin: 6px 0 0;
	}

	.no-reviews {
		color: #6c7588;
		font-size: 15px;
		padding: 20px 0;
	}

	@media (max-width: 991px) {
		.main-image-card {
			height: 380px;
		}

		.gallery-nav-btn {
			opacity: 1;
			width: 36px;
			height: 36px;
			font-size: 13px;
		}

		.gallery-hint {
			display: none;
		}

		.specs-grid,
		.chars-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}

	@media (max-width: 575px) {
		.product-details-container {
			padding: 18px 0 48px;
		}

		.product-details-container :global(.container) {
			padding-right: 16px;
			padding-left: 16px;
		}

		.product-details-container :global(.row.gx-5) {
			--bs-gutter-y: 18px;
		}

		.gallery-wrapper {
			gap: 12px;
			align-items: stretch;
		}

		.main-image-card {
			height: auto;
			aspect-ratio: 1 / 1;
			max-width: none;
			padding: 12px;
			border-radius: 18px;
			background: #f8fafc;
		}

		.main-image {
			width: 100%;
			height: 100%;
			object-fit: contain;
		}

		.thumbnail-row {
			justify-content: flex-start;
			flex-wrap: nowrap;
			gap: 8px;
			overflow-x: auto;
			padding-bottom: 2px;
			scrollbar-width: none;
		}

		.thumbnail-row::-webkit-scrollbar {
			display: none;
		}

		.thumb-scroll-btn {
			display: none;
		}

		.thumb-btn {
			flex: 0 0 auto;
			width: 54px;
			height: 54px;
			border-radius: 12px;
		}

		.gallery-action-btn {
			width: 34px;
			height: 34px;
			font-size: 13px;
		}

		.gallery-counter {
			bottom: 10px;
			right: 10px;
			font-size: 11px;
			padding: 3px 8px;
		}

		.details-content {
			gap: 0;
			margin-top: 8px;
			padding: 0;
			border: 0;
			border-radius: 0;
			background: transparent;
			box-shadow: none;
		}

		.product-title {
			font-size: 24px;
			font-weight: 800;
			line-height: 1.12;
			margin-bottom: 8px;
			letter-spacing: 0;
		}

		.code-badge-wrap {
			margin-bottom: 14px;
		}

		.code-badge {
			display: inline-flex;
			align-items: center;
			min-height: 30px;
			font-size: 12px;
			padding: 6px 12px;
			border-radius: 999px;
			background: #f1f5f9;
			color: #64748b;
		}

		.specs-grid-box {
			padding: 12px;
			margin-bottom: 12px;
			border: 1px solid #e6edf7;
			border-radius: 16px;
			background: #ffffff;
		}

		.specs-grid,
		.chars-grid {
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 8px;
		}

		.spec-col,
		.char-col {
			min-width: 0;
			padding: 10px;
			border-radius: 12px;
			background: #f8fafc;
		}

		.spec-label,
		.char-label {
			font-size: 10.5px;
			margin-bottom: 5px;
			color: #7b8496;
		}

		.spec-val,
		.char-val {
			font-size: 13px;
			line-height: 1.3;
			color: var(--theme);
			font-weight: 700;
			overflow-wrap: anywhere;
		}

		.characteristics-section {
			margin-bottom: 14px;
			padding: 12px;
			border: 1px solid #e6edf7;
			border-radius: 16px;
			background: #ffffff;
		}

		.char-title {
			margin-bottom: 10px;
			font-size: 15px;
			line-height: 1.2;
		}

		.option-group {
			margin-bottom: 12px;
			padding: 12px;
			border: 1px solid #e6edf7;
			border-radius: 16px;
			background: #ffffff;
		}

		.option-title {
			display: block;
			margin-bottom: 8px;
			font-size: 13px;
			font-weight: 800;
			color: #1f2937;
		}

		.color-swatches,
		.size-swatches {
			gap: 7px;
			flex-wrap: nowrap;
			overflow-x: auto;
			padding-bottom: 2px;
			scrollbar-width: none;
		}

		.color-swatches::-webkit-scrollbar,
		.size-swatches::-webkit-scrollbar {
			display: none;
		}

		.color-chip,
		.size-chip {
			flex: 0 0 auto;
			border-radius: 999px;
			font-size: 12px;
			min-height: 38px;
			background: #f8fafc;
			border-color: #e2e8f0;
		}

		.color-chip.active,
		.size-chip.active {
			background: #fff;
			box-shadow: 0 0 0 1px color-mix(in srgb, var(--theme) 18%, transparent);
		}

		.color-text {
			max-width: 76px;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.action-card {
			padding: 14px;
			margin-top: 14px;
			border: 1px solid #dbe6f3;
			border-radius: 20px;
			background: #ffffff;
			box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
		}

		.price-row {
			align-items: center;
			gap: 10px;
			margin-bottom: 14px;
		}

		.price-display {
			flex: 1;
			flex-wrap: wrap;
			gap: 7px;
		}

		.main-price {
			width: 100%;
			font-size: 28px;
			line-height: 1.05;
		}

		.old-price {
			font-size: 14px;
		}

		.qty-control {
			flex: 0 0 auto;
			height: 38px;
			border-radius: 999px;
			background: #fff;
			border-color: #d7e0ee;
		}

		.qty-val {
			width: 34px;
		}

		.add-to-cart-btn {
			min-height: 48px;
			border-radius: 16px;
			padding: 13px 16px;
			font-size: 14px;
			box-shadow: 0 10px 22px color-mix(in srgb, var(--theme) 22%, transparent);
		}

		.sub-actions {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 8px;
			margin-top: 12px;
		}

		.sub-action-btn {
			justify-content: center;
			min-height: 42px;
			padding: 9px;
			border: 1px solid #e2e6ec;
			border-radius: 14px;
			background: #fff;
			font-size: 12px;
			text-align: center;
		}

		.sub-action-btn span {
			display: -webkit-box;
			overflow: hidden;
			-webkit-box-orient: vertical;
			-webkit-line-clamp: 1;
		}

		.details-tabs-wrapper {
			padding-top: 24px;
		}

		.tabs-nav {
			gap: 8px;
			overflow-x: auto;
			margin-bottom: 16px;
			border-bottom: 0;
			padding-bottom: 2px;
			scrollbar-width: none;
		}

		.tabs-nav::-webkit-scrollbar {
			display: none;
		}

		.tab-btn {
			flex: 0 0 auto;
			padding: 10px 13px;
			border: 1px solid #e2e6ec;
			border-radius: 999px;
			background: #fff;
			font-size: 13px;
			margin-bottom: 0;
		}

		.tab-btn.active {
			border-color: var(--theme);
			background: color-mix(in srgb, var(--theme) 8%, #fff);
		}

		.description-body {
			font-size: 14px;
			line-height: 1.65;
		}

		.specs-table {
			border-radius: 10px;
		}

		.specs-table td {
			padding: 12px;
			font-size: 13px;
		}

		.table-label {
			min-width: 130px;
		}

		.review-entry {
			gap: 10px;
			padding: 12px;
		}

		.review-avatar {
			width: 36px;
			height: 36px;
			font-size: 14px;
		}

		.review-meta {
			flex-direction: column;
			gap: 2px;
		}

		.rate-picker {
			flex-wrap: wrap;
		}

		.review-form textarea {
			min-height: 110px;
		}

		.similar-products-header {
			align-items: flex-start;
			gap: 12px;
			margin-bottom: 12px;
			padding-bottom: 12px;
		}

		.similar-title {
			font-size: 20px;
		}

		.similar-subtitle,
		.view-all-link {
			font-size: 12px;
		}

		.similar-products-grid {
			--bs-gutter-x: 12px;
			--bs-gutter-y: 12px;
		}

		.similar-products-grid :global(.best-seller-product-items-two) {
			height: 100%;
			padding: 8px;
			border-width: 1px;
			border-radius: 14px;
			overflow: hidden;
		}

		.similar-products-grid :global(.best-seller-product-items-two__thumb) {
			aspect-ratio: 1 / 1;
			border-radius: 12px;
			overflow: hidden;
			background: #f5f7fa;
		}

		.similar-products-grid :global(.best-seller-product-items-two__thumb img) {
			width: 100%;
			height: 100%;
			display: block;
			object-fit: cover;
		}

		.similar-products-grid :global(.best-seller-product-items-two__content) {
			display: block;
			margin-top: 8px;
		}

		.similar-products-grid :global(.best-seller-product-items-two__details--subtitle) {
			margin-bottom: 2px;
			font-size: 11px;
			line-height: 1.2;
		}

		.similar-products-grid :global(.best-seller-product-items-two__details--title) {
			margin-bottom: 4px;
			font-size: 13px;
			line-height: 1.25;
		}

		.similar-products-grid :global(.best-seller-product-items-two__details--title a) {
			display: -webkit-box;
			overflow: hidden;
			-webkit-box-orient: vertical;
			-webkit-line-clamp: 1;
		}

		.similar-products-grid :global(.best-seller-product-items-two__details--price) {
			display: flex;
			flex-wrap: wrap;
			gap: 4px;
		}

		.similar-products-grid :global(.best-seller-product-items-two__details--price .offer-price),
		.similar-products-grid :global(.best-seller-product-items-two__details--price .original-price) {
			margin-right: 0;
			font-size: 12px;
			line-height: 1.25;
		}

		.similar-products-grid :global(.best-seller-product-items-two .icon-box2) {
			top: 14px;
			right: 14px;
			left: auto;
			display: flex;
			gap: 6px;
			opacity: 1;
			visibility: visible;
			transform: none;
		}

		.similar-products-grid :global(.best-seller-product-items-two .icon-box2 button) {
			width: 30px;
			height: 30px;
			font-size: 12.5px;
		}
	}
</style>
