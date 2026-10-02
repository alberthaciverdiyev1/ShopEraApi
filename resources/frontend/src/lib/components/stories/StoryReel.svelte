<script lang="ts">
	import { onMount } from 'svelte';
	import {
		fetchStoryVideos,
		initViewedStories,
		storyVideos,
		viewedStoryIds,
		type ApiStoryVideo
	} from '$lib/services/stories';
	import StoryViewer from '$lib/components/stories/StoryViewer.svelte';
	import { translate } from '$lib/i18n';

	let activeStoryIndex = $state<number | null>(null);
	let reelContainer = $state<HTMLElement | null>(null);
	let canScrollLeft = $state(false);
	let canScrollRight = $state(false);

	const stories = $derived($storyVideos);
	const viewed = $derived($viewedStoryIds);

	onMount(() => {
		initViewedStories();
		fetchStoryVideos().then(() => updateScrollButtons());
	});

	function updateScrollButtons() {
		if (!reelContainer) return;
		canScrollLeft = reelContainer.scrollLeft > 10;
		canScrollRight =
			reelContainer.scrollLeft < reelContainer.scrollWidth - reelContainer.clientWidth - 10;
	}

	function scrollBy(offset: number) {
		if (!reelContainer) return;
		reelContainer.scrollBy({ left: offset, behavior: 'smooth' });
	}

	function openStory(index: number) {
		activeStoryIndex = index;
	}

	function closeStory() {
		activeStoryIndex = null;
	}

	function cleanTitle(title?: string | null): string {
		if (!title) return '';
		return title.length > 14 ? title.slice(0, 13) + '…' : title;
	}
</script>

{#if stories.length > 0}
	<section class="story-reel-section" aria-label={$translate('Product Stories')}>
		<div class="container">
			<div class="story-reel-wrapper">
				<!-- Desktop Navigation Arrows -->
				{#if canScrollLeft}
					<button
						type="button"
						class="story-reel-arrow prev d-none d-md-flex"
						onclick={() => scrollBy(-240)}
						aria-label={$translate('Previous')}
					>
						<i class="fa-solid fa-chevron-left"></i>
					</button>
				{/if}

				{#if canScrollRight}
					<button
						type="button"
						class="story-reel-arrow next d-none d-md-flex"
						onclick={() => scrollBy(240)}
						aria-label={$translate('Next')}
					>
						<i class="fa-solid fa-chevron-right"></i>
					</button>
				{/if}

				<!-- Stories Scroll Strip -->
				<div
					class="story-reel-track"
					bind:this={reelContainer}
					onscroll={updateScrollButtons}
				>
					{#each stories as story, index (story.id)}
						{@const isViewed = viewed.has(story.id)}
						<button
							type="button"
							class="story-bubble"
							class:viewed={isViewed}
							onclick={() => openStory(index)}
							aria-label={story.product?.title ?? `Story ${index + 1}`}
						>
							<div class="story-ring" class:unseen={!isViewed}>
								<div class="story-thumb-box">
									{#if story.image}
										<img src={story.image} alt={story.product?.title ?? 'Story'} class="story-thumb" loading="lazy" />
									{:else if story.product?.image}
										<img src={story.product.image} alt={story.product.title} class="story-thumb" loading="lazy" />
									{:else}
										<div class="story-thumb-fallback">
											<i class="fa-regular fa-image"></i>
										</div>
									{/if}
									{#if story.video}
										<span class="story-play-badge" aria-hidden="true">
											<i class="fa-solid fa-play"></i>
										</span>
									{/if}
								</div>
							</div>
							<span class="story-name">
								{cleanTitle(story.product?.title) || $translate('Story')}
							</span>
						</button>
					{/each}
				</div>
			</div>
		</div>
	</section>
{/if}

{#if activeStoryIndex !== null && stories[activeStoryIndex]}
	<StoryViewer
		{stories}
		initialIndex={activeStoryIndex}
		onclose={closeStory}
	/>
{/if}

<style>
	.story-reel-section {
		padding: 14px 0 6px;
		background: var(--body);
		border-bottom: 1px solid rgba(15, 23, 42, 0.05);
	}

	.story-reel-wrapper {
		position: relative;
		display: flex;
		align-items: center;
	}

	.story-reel-track {
		display: flex;
		align-items: flex-start;
		gap: 16px;
		overflow-x: auto;
		overflow-y: hidden;
		-webkit-overflow-scrolling: touch;
		scroll-behavior: smooth;
		scrollbar-width: none;
		touch-action: pan-x;
		padding: 6px 4px 8px;
		width: 100%;
	}

	.story-reel-track::-webkit-scrollbar {
		display: none;
	}

	.story-bubble {
		flex: 0 0 auto;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 6px;
		background: transparent;
		border: 0;
		padding: 0;
		cursor: pointer;
		outline: none;
		max-width: 82px;
		text-align: center;
		transition: transform 0.15s ease;
		user-select: none;
		touch-action: pan-x;
	}

	.story-bubble:hover {
		transform: translateY(-2px);
	}

	.story-ring {
		width: 72px;
		height: 72px;
		border-radius: 50%;
		padding: 2.5px;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #e2e8f0;
		transition: transform 0.2s ease, box-shadow 0.2s ease;
	}

	.story-ring.unseen {
		background: linear-gradient(135deg, #e91e79 0%, #ff6b8b 45%, #ff9800 100%);
		box-shadow: 0 4px 14px rgba(233, 30, 121, 0.25);
	}

	.story-bubble:hover .story-ring.unseen {
		box-shadow: 0 6px 18px rgba(233, 30, 121, 0.35);
	}

	.story-thumb-box {
		position: relative;
		width: 100%;
		height: 100%;
		border-radius: 50%;
		border: 2px solid #ffffff;
		background: #f8fafc;
		overflow: hidden;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.story-thumb {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: transform 0.25s ease;
	}

	.story-bubble:hover .story-thumb {
		transform: scale(1.08);
	}

	.story-thumb-fallback {
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #94a3b8;
		font-size: 18px;
		background: #f1f5f9;
	}

	.story-play-badge {
		position: absolute;
		bottom: 1px;
		right: 1px;
		width: 18px;
		height: 18px;
		border-radius: 50%;
		background: rgba(15, 23, 42, 0.75);
		backdrop-filter: blur(4px);
		border: 1.5px solid #fff;
		color: #fff;
		font-size: 7px;
		display: flex;
		align-items: center;
		justify-content: center;
		padding-left: 1px;
	}

	.story-name {
		font-size: 11.5px;
		font-weight: 700;
		color: #1e293b;
		line-height: 1.2;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		max-width: 76px;
	}

	.story-bubble.viewed .story-name {
		color: #64748b;
		font-weight: 600;
	}

	.story-reel-arrow {
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
		z-index: 5;
		width: 34px;
		height: 34px;
		border-radius: 50%;
		border: 1px solid #e2e8f0;
		background: #ffffff;
		color: #0f172a;
		font-size: 12px;
		display: flex;
		align-items: center;
		justify-content: center;
		box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
		cursor: pointer;
		transition: background 0.15s ease, transform 0.15s ease;
	}

	.story-reel-arrow:hover {
		background: #f8fafc;
		transform: translateY(-50%) scale(1.08);
	}

	.story-reel-arrow.prev {
		left: -12px;
	}

	.story-reel-arrow.next {
		right: -12px;
	}

	@media (max-width: 767.98px) {
		.story-reel-section {
			padding: 10px 0 4px;
		}

		.story-reel-track {
			gap: 12px;
			padding: 4px 2px 6px;
		}

		.story-ring {
			width: 62px;
			height: 62px;
		}

		.story-bubble {
			max-width: 68px;
		}

		.story-name {
			font-size: 10.5px;
			max-width: 66px;
		}
	}
</style>
