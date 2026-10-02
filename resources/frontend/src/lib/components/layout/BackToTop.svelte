<script lang="ts">
	import { onMount } from 'svelte';

	onMount(() => {
		const button = document.getElementById('back-top');
		const onScroll = () => button?.classList.toggle('show', window.scrollY > 20);
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
		return () => window.removeEventListener('scroll', onScroll);
	});

	function toTop() {
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}
</script>

<!-- Back To Top Start -->
<!-- svelte-ignore a11y_consider_explicit_label -->
<button id="back-top" class="back-to-top" onclick={toTop}>
    <i class="fa-solid fa-chevron-up"></i>
</button>

<style>
	:global(#back-top.back-to-top) {
		bottom: 92px !important;
		right: 26px !important;
		width: 48px !important;
		height: 48px !important;
		line-height: 48px !important;
		z-index: 990 !important;
		box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15) !important;
	}

	@media (max-width: 991.98px) {
		:global(#back-top.back-to-top) {
			bottom: calc(134px + env(safe-area-inset-bottom, 0px)) !important;
			right: 18px !important;
			width: 44px !important;
			height: 44px !important;
			line-height: 44px !important;
			font-size: 15px !important;
		}
	}
</style>
