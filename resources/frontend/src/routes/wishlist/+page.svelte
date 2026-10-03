<script lang="ts">
	import { translate } from '$lib/i18n';
	import { goto } from '$app/navigation';
	import { features } from '$lib/services/features';

	// The layout loads /features once — react to the store instead of re-fetching.
	$effect(() => {
		if ($features.favorites === false) goto('/');
	});
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Wishlist4 from '$lib/components/pages/wishlist/Wishlist4.svelte';
</script>

<svelte:head>
	<title>{$translate('Wishlist')} — Snaker</title>
</svelte:head>
<Breadcrumb title="İstək Siyahısı" />
<Wishlist4 />
