<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import { goto } from '$app/navigation';
	import { markStoryAsViewed, storyProductUrl, type ApiStoryVideo } from '$lib/services/stories';
	import { addProductToCart } from '$lib/services/basket-actions';
	import { basketProductIds } from '$lib/services/basket';
	import { translate } from '$lib/i18n';

	let {
		stories,
		initialIndex = 0,
		onclose
	}: {
		stories: ApiStoryVideo[];
		initialIndex?: number;
		onclose: () => void;
	} = $props();

	let currentIndex = $state(0);
	$effect(() => {
		currentIndex = initialIndex;
	});
	let isPaused = $state(false);
	let isMuted = $state(true);
	let progress = $state(0); // 0 to 100 for current story
	let videoEl = $state<HTMLVideoElement | null>(null);
	let holdTimeout: ReturnType<typeof setTimeout> | null = null;
	let isHolding = $state(false);
	let addedToast = $state(false);

	const currentStory = $derived(stories[currentIndex]);
	const product = $derived(currentStory?.product);

	$effect(() => {
		if (currentStory) {
			markStoryAsViewed(currentStory.id);
		}
	});

	function nextStory() {
		if (currentIndex < stories.length - 1) {
			currentIndex++;
			progress = 0;
		} else {
			onclose();
		}
	}

	function prevStory() {
		if (currentIndex > 0) {
			currentIndex--;
			progress = 0;
		} else {
			// Restart current
			if (videoEl) videoEl.currentTime = 0;
			progress = 0;
		}
	}

	// Image stories have no video to end, so advance on a 5s timer.
	$effect(() => {
		const story = currentStory;
		if (!story || story.video || !story.image) return;

		progress = 0;
		const started = Date.now();
		const duration = 5000;
		const timer = setInterval(() => {
			if (isHolding) return;
			progress = Math.min(100, ((Date.now() - started) / duration) * 100);
			if (progress >= 100) {
				clearInterval(timer);
				nextStory();
			}
		}, 100);

		return () => clearInterval(timer);
	});

	function handleTimeUpdate() {
		if (!videoEl || isHolding) return;
		if (videoEl.duration > 0) {
			progress = (videoEl.currentTime / videoEl.duration) * 100;
		}
	}

	function handleVideoEnded() {
		nextStory();
	}

	function toggleMute() {
		isMuted = !isMuted;
		if (videoEl) videoEl.muted = isMuted;
	}

	function handlePointerDown(e: PointerEvent) {
		// Only pause if clicking on video area, not buttons
		const target = e.target as HTMLElement;
		if (target.closest('button') || target.closest('a')) return;

		holdTimeout = setTimeout(() => {
			isHolding = true;
			if (videoEl) videoEl.pause();
		}, 180);
	}

	function handlePointerUp(e: PointerEvent) {
		if (holdTimeout) {
			clearTimeout(holdTimeout);
			holdTimeout = null;
		}

		if (isHolding) {
			isHolding = false;
			if (videoEl) videoEl.play().catch(() => {});
			return;
		}

		// Was a quick tap: determine left vs right tap
		const target = e.target as HTMLElement;
		if (target.closest('button') || target.closest('a')) return;

		const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
		const clickX = e.clientX - rect.left;
		if (clickX < rect.width * 0.3) {
			prevStory();
		} else {
			nextStory();
		}
	}

	function handleKeydown(e: KeyboardEvent) {
		if (e.key === 'Escape') {
			onclose();
		} else if (e.key === 'ArrowRight' || e.key === ' ') {
			e.preventDefault();
			nextStory();
		} else if (e.key === 'ArrowLeft') {
			e.preventDefault();
			prevStory();
		} else if (e.key === 'm' || e.key === 'M') {
			toggleMute();
		}
	}

	async function handleAddToCart(e: MouseEvent) {
		e.stopPropagation();
		if (!product?.id) return;
		await addProductToCart(product.id);
		addedToast = true;
		setTimeout(() => (addedToast = false), 2000);
	}

	function handleViewProduct(e: MouseEvent) {
		e.stopPropagation();
		if (!currentStory) return;
		onclose();
		goto(storyProductUrl(currentStory));
	}

	onMount(() => {
		document.body.style.overflow = 'hidden';
		window.addEventListener('keydown', handleKeydown);
	});

	onDestroy(() => {
		document.body.style.overflow = '';
		window.removeEventListener('keydown', handleKeydown);
	});
</script>

<div class="story-overlay" role="dialog" aria-modal="true" aria-label="Story Viewer">
	<button class="story-backdrop" onclick={onclose} aria-label={$translate('Close')}></button>

	<!-- Desktop / Mobile Story Frame -->
	<div
		class="story-stage"
		role="region"
		aria-label="Story Player"
		onpointerdown={handlePointerDown}
		onpointerup={handlePointerUp}
		onpointercancel={handlePointerUp}
	>
		<!-- Top Progress Bars -->
		<div class="story-progress-row">
			{#each stories as story, index (story.id)}
				<div class="story-progress-track">
					<div
						class="story-progress-bar"
						style:width={index < currentIndex ? '100%' : index === currentIndex ? `${progress}%` : '0%'}
					></div>
				</div>
			{/each}
		</div>

		<!-- Top Header Controls -->
		<div class="story-header">
			<div class="story-author">
				{#if product?.image}
					<img src={product.image} alt={product.title} class="story-avatar" />
				{:else}
					<div class="story-avatar-placeholder">
						<i class="fa-solid fa-bag-shopping"></i>
					</div>
				{/if}
				<div class="story-author-info">
					<span class="story-author-title">{product?.title ?? $translate('Product')}</span>
					<span class="story-counter">{currentIndex + 1} / {stories.length}</span>
				</div>
			</div>

			<div class="story-header-actions">
				<button
					type="button"
					class="story-btn"
					onclick={toggleMute}
					aria-label={isMuted ? $translate('Unmute') : $translate('Mute')}
				>
					<i class={isMuted ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high'}></i>
				</button>
				<button
					type="button"
					class="story-btn"
					onclick={onclose}
					aria-label={$translate('Close')}
				>
					<i class="fa-solid fa-xmark"></i>
				</button>
			</div>
		</div>

		<!-- Story media: video when present, otherwise the story image -->
		{#if currentStory}
			{#key currentStory.id}
				{#if currentStory.video}
					<video
						bind:this={videoEl}
						src={currentStory.video}
						autoplay
						playsinline
						muted={isMuted}
						ontimeupdate={handleTimeUpdate}
						onended={handleVideoEnded}
						class="story-video"
					></video>
				{:else if currentStory.image}
					<img src={currentStory.image} alt={product?.title ?? 'Story'} class="story-video story-image" />
				{/if}
			{/key}
		{/if}

		<!-- Pause Indicator -->
		{#if isHolding}
			<div class="story-pause-pill">
				<i class="fa-solid fa-pause"></i>
			</div>
		{/if}

		<!-- Added to cart mini toast -->
		{#if addedToast}
			<div class="story-toast">
				<i class="fa-solid fa-circle-check"></i>
				<span>{$translate('Added to cart!')}</span>
			</div>
		{/if}

		<!-- Bottom Floating Product Card -->
		{#if product}
			<div class="story-product-card">
				<a href={storyProductUrl(currentStory)} onclick={handleViewProduct} class="story-product-thumb-wrap">
					{#if product.image}
						<img src={product.image} alt={product.title} class="story-product-thumb" />
					{:else}
						<div class="story-product-thumb-placeholder">
							<i class="fa-solid fa-image"></i>
						</div>
					{/if}
				</a>

				<div class="story-product-details">
					<a href={storyProductUrl(currentStory)} onclick={handleViewProduct} class="story-product-name">
						{product.title}
					</a>
					<div class="story-product-price">
						<strong>${Number(product.discount || product.price || 0).toFixed(2)}</strong>
						{#if product.discount && product.price && product.discount < product.price}
							<del>${Number(product.price).toFixed(2)}</del>
						{/if}
					</div>
				</div>

				<div class="story-product-actions">
					<button
						type="button"
						class="story-cart-btn"
						class:in-cart={$basketProductIds.has(product.id)}
						onclick={handleAddToCart}
						aria-label={$translate('Add to cart')}
					>
						<i class="fa-solid fa-cart-shopping"></i>
					</button>
					<button
						type="button"
						class="story-view-btn"
						onclick={handleViewProduct}
					>
						<span>{$translate('View')}</span>
						<i class="fa-solid fa-chevron-right"></i>
					</button>
				</div>
			</div>
		{/if}

		<!-- Left / Right Tap Hints for Desktop -->
		<button
			type="button"
			class="story-nav-prev d-none d-md-flex"
			onclick={(e) => { e.stopPropagation(); prevStory(); }}
			aria-label={$translate('Previous')}
		>
			<i class="fa-solid fa-chevron-left"></i>
		</button>
		<button
			type="button"
			class="story-nav-next d-none d-md-flex"
			onclick={(e) => { e.stopPropagation(); nextStory(); }}
			aria-label={$translate('Next')}
		>
			<i class="fa-solid fa-chevron-right"></i>
		</button>
	</div>
</div>

<style>
	.story-overlay {
		position: fixed;
		inset: 0;
		z-index: 10000;
		display: flex;
		align-items: center;
		justify-content: center;
		background: rgba(0, 0, 0, 0.88);
		backdrop-filter: blur(12px);
		touch-action: none;
	}

	.story-backdrop {
		position: absolute;
		inset: 0;
		background: transparent;
		border: 0;
		cursor: default;
	}

	.story-stage {
		position: relative;
		z-index: 2;
		width: 100%;
		max-width: 420px;
		height: 100%;
		max-height: 860px;
		border-radius: 20px;
		background: #000;
		overflow: hidden;
		display: flex;
		flex-direction: column;
		box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
		user-select: none;
	}

	@media (max-width: 640px) {
		.story-stage {
			max-width: 100vw;
			height: 100dvh;
			max-height: 100dvh;
			border-radius: 0;
		}
	}

	.story-progress-row {
		position: absolute;
		top: 10px;
		left: 10px;
		right: 10px;
		z-index: 10;
		display: flex;
		gap: 4px;
	}

	.story-progress-track {
		flex: 1;
		height: 3px;
		background: rgba(255, 255, 255, 0.35);
		border-radius: 999px;
		overflow: hidden;
	}

	.story-progress-bar {
		height: 100%;
		background: #ffffff;
		border-radius: 999px;
		transition: width 0.08s linear;
	}

	.story-header {
		position: absolute;
		top: 22px;
		left: 12px;
		right: 12px;
		z-index: 10;
		display: flex;
		align-items: center;
		justify-content: space-between;
		pointer-events: auto;
	}

	.story-author {
		display: flex;
		align-items: center;
		gap: 10px;
		background: rgba(0, 0, 0, 0.45);
		backdrop-filter: blur(10px);
		padding: 4px 12px 4px 5px;
		border-radius: 999px;
		max-width: calc(100% - 100px);
	}

	.story-avatar,
	.story-avatar-placeholder {
		width: 34px;
		height: 34px;
		border-radius: 50%;
		object-fit: cover;
		flex-shrink: 0;
		border: 1.5px solid #fff;
		background: #1e293b;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #fff;
		font-size: 13px;
	}

	.story-author-info {
		display: flex;
		flex-direction: column;
		min-width: 0;
	}

	.story-author-title {
		color: #fff;
		font-size: 12.5px;
		font-weight: 700;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		line-height: 1.2;
	}

	.story-counter {
		color: rgba(255, 255, 255, 0.7);
		font-size: 10px;
		font-weight: 600;
	}

	.story-header-actions {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.story-btn {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		border: 0;
		background: rgba(0, 0, 0, 0.45);
		backdrop-filter: blur(10px);
		color: #fff;
		font-size: 14px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: background 0.15s ease, transform 0.15s ease;
	}

	.story-btn:hover {
		background: rgba(0, 0, 0, 0.75);
		transform: scale(1.06);
	}

	.story-video {
		width: 100%;
		height: 100%;
		object-fit: cover;
		background: #000;
	}

	.story-pause-pill {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		z-index: 10;
		width: 58px;
		height: 58px;
		border-radius: 50%;
		background: rgba(0, 0, 0, 0.6);
		backdrop-filter: blur(10px);
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 20px;
		pointer-events: none;
		animation: pulse 1s infinite alternate;
	}

	@keyframes pulse {
		from { transform: translate(-50%, -50%) scale(0.95); opacity: 0.8; }
		to { transform: translate(-50%, -50%) scale(1.05); opacity: 1; }
	}

	.story-toast {
		position: absolute;
		top: 80px;
		left: 50%;
		transform: translateX(-50%);
		z-index: 25;
		background: rgba(16, 185, 129, 0.95);
		color: #fff;
		font-size: 13px;
		font-weight: 700;
		padding: 8px 18px;
		border-radius: 999px;
		display: flex;
		align-items: center;
		gap: 8px;
		box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
		animation: fadeInDown 0.25s ease-out;
	}

	@keyframes fadeInDown {
		from { opacity: 0; transform: translate(-50%, -10px); }
		to { opacity: 1; transform: translate(-50%, 0); }
	}

	.story-product-card {
		position: absolute;
		bottom: calc(16px + env(safe-area-inset-bottom, 0px));
		left: 14px;
		right: 14px;
		z-index: 20;
		background: rgba(255, 255, 255, 0.92);
		backdrop-filter: blur(16px);
		border: 1px solid rgba(255, 255, 255, 0.4);
		border-radius: 18px;
		padding: 10px 12px;
		display: flex;
		align-items: center;
		gap: 12px;
		box-shadow: 0 12px 36px rgba(0, 0, 0, 0.35);
	}

	.story-product-thumb-wrap {
		width: 48px;
		height: 48px;
		border-radius: 12px;
		overflow: hidden;
		flex-shrink: 0;
		background: #f1f5f9;
		display: block;
	}

	.story-product-thumb {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.story-product-thumb-placeholder {
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #94a3b8;
		font-size: 18px;
	}

	.story-product-details {
		flex: 1;
		min-width: 0;
	}

	.story-product-name {
		display: block;
		font-size: 13px;
		font-weight: 800;
		color: #0f172a;
		text-decoration: none;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		line-height: 1.25;
	}

	.story-product-price {
		margin-top: 3px;
		display: flex;
		align-items: baseline;
		gap: 6px;
	}

	.story-product-price strong {
		color: #e91e79;
		font-size: 14px;
		font-weight: 800;
	}

	.story-product-price del {
		color: #94a3b8;
		font-size: 11px;
	}

	.story-product-actions {
		display: flex;
		align-items: center;
		gap: 6px;
		flex-shrink: 0;
	}

	.story-cart-btn {
		width: 38px;
		height: 38px;
		border-radius: 12px;
		border: 1px solid #fbcfe8;
		background: #fff0f7;
		color: #e91e79;
		font-size: 14px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: background 0.15s ease, transform 0.1s ease;
	}

	.story-cart-btn:hover,
	.story-cart-btn.in-cart {
		background: #e91e79;
		color: #fff;
	}

	.story-view-btn {
		height: 38px;
		padding: 0 14px;
		border-radius: 12px;
		border: 0;
		background: #0f172a;
		color: #fff;
		font-size: 12.5px;
		font-weight: 700;
		display: flex;
		align-items: center;
		gap: 6px;
		cursor: pointer;
		transition: background 0.15s ease;
	}

	.story-view-btn:hover {
		background: #1e293b;
	}

	.story-nav-prev,
	.story-nav-next {
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
		z-index: 15;
		width: 44px;
		height: 44px;
		border-radius: 50%;
		border: 0;
		background: rgba(255, 255, 255, 0.25);
		backdrop-filter: blur(8px);
		color: #fff;
		font-size: 16px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: background 0.15s ease, transform 0.15s ease;
	}

	.story-nav-prev {
		left: -60px;
	}

	.story-nav-next {
		right: -60px;
	}

	.story-nav-prev:hover,
	.story-nav-next:hover {
		background: rgba(255, 255, 255, 0.5);
		transform: translateY(-50%) scale(1.08);
	}
</style>
