<script lang="ts">
	import type { ApiPromoBlock } from '$lib/services/promoBlocks';

	let { blocks = [] }: { blocks?: ApiPromoBlock[] } = $props();

	const messages = $derived.by(() => {
		return blocks
			.map((block) => {
				const main = block.title?.trim() || block.subtitle?.trim() || block.badge?.trim();
				if (!main) return '';
				return block.badge && block.title ? `${block.badge} ${block.title}` : main;
			})
			.filter(Boolean);
	});
	const loopMessages = $derived([...messages, ...messages, ...messages]);
</script>

{#if messages.length > 0}
	<!-- Marquee Section -->
	<div class="marquee-section1 pt-20">
		<div class="container">
			<div class="mycustom-marque">
				<div class="scrolling-wrap">
					<div class="comm">
						{#each loopMessages as message, index (`primary-${index}-${message}`)}
							<div><img src="/assets/images/icon/starIcon1_1.svg" alt="img"></div>
							<div class="cmn-textslide">{message}</div>
						{/each}
					</div>
					<div class="comm">
						{#each loopMessages as message, index (`secondary-${index}-${message}`)}
							<div><img src="/assets/images/icon/starIcon1_1.svg" alt="img"></div>
							<div class="cmn-textslide">{message}</div>
						{/each}
					</div>
				</div>
			</div>
		</div>
	</div>
{/if}

<style>
	@media (max-width: 767.98px) {
		.mycustom-marque,
		.scrolling-wrap {
			border-radius: 18px !important;
		}
	}
</style>
