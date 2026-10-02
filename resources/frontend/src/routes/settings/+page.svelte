<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Account from '$lib/components/pages/settings/Account.svelte';
</script>

<svelte:head>
	<title>{$translate('Account')} — ShopEra</title>
</svelte:head>

<Breadcrumb title="Settings" parent="User Dashboard" />
<Account />
