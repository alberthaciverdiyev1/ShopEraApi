<script lang="ts">
	import { onMount } from 'svelte';
	import { fetchSubscription, type ApiSubscription } from '$lib/services/subscription';
	import { translate } from '$lib/i18n';

	let sub = $state<ApiSubscription | null>(null);

	onMount(async () => {
		sub = await fetchSubscription();
	});
</script>

{#if sub?.blocked}
	<div class="fox-sub-notice blocked">
		<i class="fa-solid fa-circle-exclamation"></i>
		{$translate('Store subscription is inactive. Orders are temporarily paused.')}
	</div>
{:else if sub?.past_due}
	<div class="fox-sub-notice warn">
		<i class="fa-solid fa-triangle-exclamation"></i>
		{$translate('Subscription payment is overdue — please renew.')}
	</div>
{/if}

<style>
	.fox-sub-notice {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		padding: 10px 16px;
		font-size: 14px;
		font-weight: 600;
		text-align: center;
	}
	.fox-sub-notice.blocked { background: #fee2e2; color: #991b1b; }
	.fox-sub-notice.warn { background: #fef3c7; color: #92400e; }
</style>
