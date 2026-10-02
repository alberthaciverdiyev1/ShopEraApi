<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import BlogClassic3 from '$lib/components/pages/blog-classic/BlogClassic3.svelte';
</script>

<svelte:head>
	<title>{$translate('Classic')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Blog Classic" />
<BlogClassic3 />
