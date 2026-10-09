<script lang="ts">
	const CART_ENABLED = false;
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Checkout3 from '$lib/components/pages/checkout/Checkout3.svelte';
</script>

<svelte:head>
	<title>{$translate('Checkout')} — Snaker</title>
</svelte:head>

<Breadcrumb title="Checkout" />
{#if CART_ENABLED}<Checkout3 />{/if}
