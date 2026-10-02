<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Shop from '$lib/components/pages/shop/Shop.svelte';

	let { data } = $props();
</script>

<svelte:head>
	<title>{$translate('Shop')} — Snaker</title>
</svelte:head>

<Breadcrumb title="Shop" />
<Shop promoBlocks={data.promoBlocks ?? []} />
