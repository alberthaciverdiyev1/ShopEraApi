<script lang="ts">
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import ProductDetails from '$lib/components/pages/shop-details-one/ProductDetails.svelte';
	import { translate } from '$lib/i18n';

	let { data } = $props();
	const product = $derived(data.product);
	const description = $derived(
		(product?.description ?? '').toString().slice(0, 160)
	);
	const jsonLd = $derived(
		product
			? {
					'@context': 'https://schema.org',
					'@type': 'Product',
					name: product.title,
					description: product.description ?? '',
					image: (product.images ?? []).map((i) => i.image_path).filter(Boolean),
					offers: {
						'@type': 'Offer',
						price: Number(product.discount || product.price || 0),
						priceCurrency: 'AZN',
						availability: (product.stock_count ?? 0) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
					}
				}
			: null
	);
</script>

<svelte:head>
	<title>{product ? `${product.title} — Snaker` : 'Elan — Snaker'}</title>
	{#if product}
		<meta name="description" content={description} />
		<meta property="og:title" content={product.title} />
		<meta property="og:description" content={description} />
		<meta property="og:type" content="product" />
		{#if product.images?.[0]?.image_path}
			<meta property="og:image" content={product.images[0].image_path} />
		{/if}
		{#if jsonLd}
			{@html `<script type="application/ld+json">${JSON.stringify(jsonLd)}</script>`}
		{/if}
	{/if}
</svelte:head>

<Breadcrumb title={product?.title ?? 'Elan'} />

{#if data.error}
	<div class="container section-padding text-center"><p>{data.error}</p></div>
{:else if product}
	<ProductDetails {product} />
{:else}
	<div class="container section-padding text-center"><p>{$translate('Loading product…')}</p></div>
{/if}
