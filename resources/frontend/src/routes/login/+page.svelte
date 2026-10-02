<script lang="ts">
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Login4 from '$lib/components/pages/login/Login4.svelte';
</script>

<svelte:head>
	<title>{$translate('Login')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Login" />
<Login4 />
