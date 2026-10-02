<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Register4 from '$lib/components/pages/register/Register4.svelte';
</script>

<svelte:head>
	<title>{$translate('Register')} — ShopEra</title>
</svelte:head>
<Breadcrumb title="Register" />
<Register4 />
