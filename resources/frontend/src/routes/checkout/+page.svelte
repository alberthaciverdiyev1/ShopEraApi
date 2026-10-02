<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Checkout3 from '$lib/components/pages/checkout/Checkout3.svelte';
</script>

<svelte:head>
	<title>{$translate('Checkout')} — ShopEra</title>
</svelte:head>

<Breadcrumb title="Checkout" />
<Checkout3 />
