<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import LegalPage from '$lib/components/layout/LegalPage.svelte';
</script>

<svelte:head>
	<title>{$translate('Privacy Policy')} — Snaker</title>
</svelte:head>

<Breadcrumb title="Privacy Policy" />
<LegalPage type="main_page" />
