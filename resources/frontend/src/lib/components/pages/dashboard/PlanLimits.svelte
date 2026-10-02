<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import {
		fetchPlan,
		limitPercent,
		PLAN_LIMIT_KEYS,
		PLAN_LIMIT_LABELS,
		type ApiPlan
	} from '$lib/services/plan';

	let plan = $state<ApiPlan | null>(null);
	let loading = $state(true);

	onMount(async () => {
		plan = await fetchPlan();
		loading = false;
	});

	function format(value: number | null | undefined, isGb = false): string {
		if (value === null || value === undefined) return '—';
		const number = Number(value);
		return isGb ? `${number.toFixed(2)} GB` : String(number);
	}
</script>

<div class="plan-limits">
	<div class="plan-header">
		<h3>{$translate('Plan & Limits')}</h3>
		{#if plan?.plan}
			<span class="plan-badge" class:blocked={plan.usable === false}>{plan.plan}</span>
		{/if}
	</div>

	{#if loading}
		<p class="text-muted">{$translate('Loading plan…')}</p>
	{:else if !plan}
		<p class="text-muted">{$translate('Plan information is not available yet.')}</p>
	{:else}
		{#if plan.ends_at}
			<p class="plan-meta">Renews / ends on {new Date(plan.ends_at).toLocaleDateString()}</p>
		{/if}

		<div class="limits-grid">
			{#each PLAN_LIMIT_KEYS as key (key)}
				{@const limit = plan.limits?.[key] ?? null}
				{@const used = plan.usage?.[key] ?? 0}
				{@const percent = limitPercent(used, limit)}
				<div class="limit-card">
					<div class="limit-top">
						<span class="limit-label">{PLAN_LIMIT_LABELS[key]}</span>
						<span class="limit-value">
							{format(used, key === 'storage_gb')} /
							{limit === null ? '∞' : format(limit, key === 'storage_gb')}
						</span>
					</div>
					<div class="limit-bar" aria-hidden="true">
						<div
							class="limit-bar-fill"
							class:full={percent !== null && percent >= 100}
							style="width: {percent ?? 4}%"
						></div>
					</div>
				</div>
			{/each}
		</div>
	{/if}
</div>

<style>
	.plan-limits {
		background: #fff;
		border: 1px solid #eee;
		border-radius: 12px;
		padding: 24px;
	}
	.plan-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 6px;
	}
	.plan-header h3 {
		margin: 0;
		font-size: 20px;
	}
	.plan-badge {
		background: var(--theme, #06b6d4);
		color: #fff;
		border-radius: 999px;
		padding: 4px 14px;
		font-size: 13px;
		font-weight: 600;
	}
	.plan-badge.blocked {
		background: #ef4444;
	}
	.plan-meta {
		color: #888;
		font-size: 13px;
		margin-bottom: 18px;
	}
	.limits-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
		gap: 16px;
	}
	.limit-card {
		border: 1px solid #f0f0f0;
		border-radius: 10px;
		padding: 14px 16px;
	}
	.limit-top {
		display: flex;
		align-items: baseline;
		justify-content: space-between;
		gap: 8px;
		margin-bottom: 10px;
	}
	.limit-label {
		font-weight: 600;
		font-size: 14px;
	}
	.limit-value {
		font-size: 13px;
		color: #666;
	}
	.limit-bar {
		height: 8px;
		border-radius: 999px;
		background: #f1f1f1;
		overflow: hidden;
	}
	.limit-bar-fill {
		height: 100%;
		border-radius: 999px;
		background: var(--theme, #06b6d4);
		transition: width 0.3s ease;
	}
	.limit-bar-fill.full {
		background: #ef4444;
	}
</style>
