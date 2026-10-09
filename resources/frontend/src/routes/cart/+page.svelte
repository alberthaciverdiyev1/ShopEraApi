<script lang="ts">
	const CART_ENABLED = false;
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Cart3 from '$lib/components/pages/cart/Cart3.svelte';
</script>

<svelte:head>
	<title>{$translate('Cart')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Cart List" />
{#if CART_ENABLED}<Cart3 />{/if}
