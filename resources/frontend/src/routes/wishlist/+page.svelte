<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { fetchFeatures } from '$lib/services/features';

	onMount(async () => {
		const f = await fetchFeatures();
		if (f.favorites === false) goto('/');
	});
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Wishlist4 from '$lib/components/pages/wishlist/Wishlist4.svelte';
</script>

<svelte:head>
	<title>{$translate('Wishlist')} — Snaker</title>
</svelte:head>
<Breadcrumb title="İstək Siyahısı" />
<Wishlist4 />
