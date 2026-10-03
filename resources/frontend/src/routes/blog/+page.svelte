<script lang="ts">
	import { translate } from '$lib/i18n';
	import { goto } from '$app/navigation';
	import { features } from '$lib/services/features';

	// The layout loads /features once — react to the store instead of re-fetching.
	$effect(() => {
		if ($features.blog === false) goto('/');
	});
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Blog3 from '$lib/components/pages/blog/Blog3.svelte';
</script>

<svelte:head>
	<title>{$translate('Blog')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Blog" />
<Blog3 />
