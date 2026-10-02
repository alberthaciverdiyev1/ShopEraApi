<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import FaqList from '$lib/components/pages/faq/FaqList.svelte';
</script>

<svelte:head>
	<title>{$translate('FAQ')} — Snaker</title>
</svelte:head>

<Breadcrumb title="Faq" />
<FaqList />
