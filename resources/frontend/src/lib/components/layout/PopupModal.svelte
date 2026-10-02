<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { fetchPopup, type ApiPopup } from '$lib/services/popups';

	let popup = $state<ApiPopup | null>(null);
	let open = $state(false);

	const isVideo = $derived(!!popup && (popup.type === 'video' || (!popup.image && !!popup.video)));

	onMount(async () => {
		if (sessionStorage.getItem('popup:dismissed') === '1') return;

		const active = await fetchPopup();
		if (!active) return;

		popup = active;
		window.setTimeout(() => {
			open = true;
		}, 700);
	});

	function close() {
		open = false;
		sessionStorage.setItem('popup:dismissed', '1');
	}
</script>

{#if open && popup}
	<div class="fox-popup-overlay" role="presentation" onclick={close}>
		<div class="fox-popup-card" role="dialog" aria-modal="true" onclick={(event) => event.stopPropagation()}>
			<button type="button" class="fox-popup-close" aria-label={$translate('Bağla')} onclick={close}>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" d="M6 18 18 6M6 6l12 12" />
				</svg>
			</button>

			{#if isVideo && popup.video}
				<video src={popup.video} autoplay muted loop playsinline controls></video>
			{:else if popup.image}
				<img src={popup.image} alt="Snaker popup" />
			{/if}
		</div>
	</div>
{/if}

<style>
	.fox-popup-overlay {
		position: fixed;
		inset: 0;
		z-index: 1080;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 16px;
		background: rgba(7, 17, 31, 0.62);
		backdrop-filter: blur(3px);
		animation: fox-popup-fade 0.25s ease;
	}

	.fox-popup-card {
		position: relative;
		max-width: 640px;
		width: 100%;
		border-radius: 20px;
		overflow: hidden;
		background: #fff;
		box-shadow: 0 30px 70px rgba(7, 17, 31, 0.4);
		animation: fox-popup-in 0.3s ease;
	}

	.fox-popup-card img,
	.fox-popup-card video {
		display: block;
		width: 100%;
		max-height: 78vh;
		object-fit: cover;
	}

	.fox-popup-close {
		position: absolute;
		top: 10px;
		right: 10px;
		z-index: 2;
		width: 36px;
		height: 36px;
		display: grid;
		place-items: center;
		border: 0;
		border-radius: 50%;
		background: rgba(7, 17, 31, 0.55);
		color: #fff;
		cursor: pointer;
	}

	.fox-popup-close:hover {
		background: rgba(7, 17, 31, 0.8);
	}

	.fox-popup-close svg {
		width: 18px;
		height: 18px;
	}

	@keyframes fox-popup-fade {
		from { opacity: 0; }
		to { opacity: 1; }
	}

	@keyframes fox-popup-in {
		from { transform: translateY(14px) scale(0.97); opacity: 0; }
		to { transform: translateY(0) scale(1); opacity: 1; }
	}
</style>
