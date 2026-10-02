<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import ShopDetails3 from '$lib/components/pages/shop-details-two/ShopDetails3.svelte';
</script>

<svelte:head>
	<title>{$translate('Details Two')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Shop details page 02" />
<ShopDetails3 />
