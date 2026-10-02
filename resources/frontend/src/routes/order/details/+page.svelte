<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Dashboard3 from '$lib/components/pages/order-details/Dashboard3.svelte';
</script>

<svelte:head>
	<title>{$translate('Order Details')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Order Details" parent="User Dashboard" />
<Dashboard3 />
