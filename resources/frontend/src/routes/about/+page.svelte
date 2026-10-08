<script lang="ts">
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import TestimonialSection from '$lib/components/pages/home/TestimonialSection.svelte';
	import { translate } from '$lib/i18n';
	import { features } from '$lib/services/features';

	let { data } = $props();
</script>

<svelte:head>
	<title>{$translate('About Us')} — Snaker</title>
</svelte:head>

<Breadcrumb title={$translate('About Us')} />

<section class="about-content container section-padding">
	{#if data.html}
		<div class="about-rich">{@html data.html}</div>
	{:else}
		<p>{$translate('Content not added yet.')}</p>
	{/if}
</section>

<!-- The same admin-selected reviews as the home page (same gating) -->
{#if $features.reviews ?? false}
	<TestimonialSection featured={data.featuredReviews} />
{/if}

<style>
	.about-rich :global(h1) { font-size: 32px; font-weight: 800; margin-bottom: 12px; color: var(--title, #0f172a); }
	.about-rich :global(h3) { font-size: 20px; font-weight: 700; margin: 20px 0 8px; color: var(--title2, #111827); }
	.about-rich :global(p) { font-size: 16px; line-height: 1.7; color: var(--text, #475569); margin-bottom: 10px; }
</style>
