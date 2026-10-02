<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { fetchLegalTerm, localized, type ApiLegalTerm } from '$lib/services/content';

	let { type, title = '' }: { type: string; title?: string } = $props();

	let term = $state<ApiLegalTerm | null>(null);
	let loading = $state(true);

	onMount(async () => {
		try {
			term = await fetchLegalTerm(type);
		} catch {
			term = null;
		} finally {
			loading = false;
		}
	});
</script>

<section class="legal-section section-padding fix bg-white">
    <div class="container">
        {#if loading}
            <p class="text-center py-5">{$translate('Loading…')}</p>
        {:else if term}
            <div class="legal-content">{@html localized(term.html)}</div>
        {:else}
            <p class="text-center py-5">This page is not available yet.</p>
        {/if}
    </div>
</section>

<style>
	.legal-content :global(h1) {
		font-size: 30px;
		margin-bottom: 18px;
	}

	.legal-content :global(h3) {
		font-size: 20px;
		margin: 24px 0 10px;
	}

	.legal-content :global(p) {
		margin-bottom: 12px;
		line-height: 1.7;
		color: #555;
	}
</style>
