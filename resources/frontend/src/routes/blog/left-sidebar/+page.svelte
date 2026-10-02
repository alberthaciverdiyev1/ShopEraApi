<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import BlogClassic3 from '$lib/components/pages/blog-left-sidebar/BlogClassic3.svelte';
</script>

<svelte:head>
	<title>{$translate('Left Sidebar')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Blog Left Sidebar" />
<BlogClassic3 />
