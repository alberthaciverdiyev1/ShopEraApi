<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import OrderTracking4 from '$lib/components/pages/order-tracking/OrderTracking4.svelte';
</script>

<svelte:head>
	<title>{$translate('Order Tracking')} — ShopEra</title>
</svelte:head>
<Breadcrumb title="Order tracking" parent="User Dashboard" />
<OrderTracking4 />
