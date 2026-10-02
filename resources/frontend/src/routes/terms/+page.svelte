<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import LegalPage from '$lib/components/layout/LegalPage.svelte';
</script>

<svelte:head>
	<title>{$translate('Terms of Service')} — ShopEra</title>
</svelte:head>

<Breadcrumb title="Terms of Service" />
<LegalPage type="register_page" />
