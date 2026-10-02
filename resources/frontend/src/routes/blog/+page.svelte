<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { fetchFeatures } from '$lib/services/features';

	onMount(async () => {
		const f = await fetchFeatures();
		if (f.blog === false) goto('/');
	});
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Blog3 from '$lib/components/pages/blog/Blog3.svelte';
</script>

<svelte:head>
	<title>{$translate('Blog')} — ShopEra</title>
</svelte:head>
<Breadcrumb title="Blog" />
<Blog3 />
