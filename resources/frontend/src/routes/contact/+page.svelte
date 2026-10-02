<script lang="ts">
	import type { PageData } from './$types';
	import { translate } from '$lib/i18n';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import Contact4 from '$lib/components/pages/contact/Contact4.svelte';
	import ContactMap3 from '$lib/components/pages/contact/ContactMap3.svelte';

	let { data }: { data: PageData } = $props();
</script>

<svelte:head>
	<title>{$translate('Contact')} — Snaker</title>
</svelte:head>
<Breadcrumb title="Contact" />
<Contact4 contactInfo={data.contactInfo} />
<ContactMap3 contactInfo={data.contactInfo} />
