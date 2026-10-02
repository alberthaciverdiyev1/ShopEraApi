<script lang="ts">
	import { navigating } from '$app/state';

	let visible = $state(false);
	let timer: ReturnType<typeof setTimeout> | undefined;

	$effect(() => {
		if (navigating) {
			timer = setTimeout(() => (visible = true), 120);
		} else {
			clearTimeout(timer);
			visible = false;
		}

		return () => clearTimeout(timer);
	});
</script>

{#if visible}
	<div class="nav-progress" role="status" aria-live="polite" aria-label="Loading">
		<div class="nav-progress__bar"></div>
	</div>
{/if}

<style>
	.nav-progress {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		z-index: 100000;
		height: 3px;
		background: rgba(15, 23, 42, 0.06);
		pointer-events: none;
	}

	.nav-progress__bar {
		height: 100%;
		width: 35%;
		background: var(--theme, #06b6d4);
		box-shadow: 0 0 8px var(--theme, #06b6d4);
		animation: nav-progress 1.05s ease-in-out infinite;
	}

	@keyframes nav-progress {
		0% {
			transform: translateX(-100%);
		}
		100% {
			transform: translateX(380%);
		}
	}
</style>
