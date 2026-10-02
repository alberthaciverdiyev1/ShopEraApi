<script lang="ts">
	import { translate } from '$lib/i18n';
	import MobileCategoryCatalog from '$lib/components/pages/categories/MobileCategoryCatalog.svelte';
</script>

<svelte:head>
	<title>{$translate('Catalog')} — ShopEra</title>
</svelte:head>

<MobileCategoryCatalog />
