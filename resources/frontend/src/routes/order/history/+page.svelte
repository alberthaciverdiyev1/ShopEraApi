<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Dashboard3 from '$lib/components/pages/order-history/Dashboard3.svelte';
</script>

<svelte:head>
	<title>{$translate('Order History')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Order History" parent="User Dashboard" />
<Dashboard3 />
