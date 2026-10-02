<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import ProductDetails from '$lib/components/pages/shop-details-one/ProductDetails.svelte';

	let { data } = $props();
	const product = $derived(data.product);
</script>

<svelte:head>
	<title>{product ? product.title : 'Product'} — ShopEra</title>
</svelte:head>

<Breadcrumb title="Product details" />

{#if data.error}
	<div class="container section-padding text-center"><p>{data.error}</p></div>
{:else if product}
	<ProductDetails {product} />
{:else}
	<div class="container section-padding text-center"><p>{$translate('Loading product…')}</p></div>
{/if}
