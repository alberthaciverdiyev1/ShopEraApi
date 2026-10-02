<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Dashboard3 from '$lib/components/pages/dashboard/Dashboard3.svelte';
</script>

<svelte:head>
	<title>{$translate('Dashboard')} — Snaker</title>
</svelte:head>

<Breadcrumb title="User Dashboard" parent="User Dashboard" />
<Dashboard3 />
