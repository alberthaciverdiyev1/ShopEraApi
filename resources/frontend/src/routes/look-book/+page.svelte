<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import LookBook3 from '$lib/components/pages/look-book/LookBook3.svelte';
</script>

<svelte:head>
	<title>{$translate('Look Book')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Look Book" />
<LookBook3 />
