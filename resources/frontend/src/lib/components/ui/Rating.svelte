<script lang="ts">
	let {
		value = 0,
		count,
		size = 'md'
	}: {
		value?: number | string;
		count?: number;
		size?: 'sm' | 'md' | 'lg';
	} = $props();

	const filled = $derived(Math.round(Number(value) || 0));
</script>

<div class="rating-star rating-{size}">
	{#each Array(5) as _, index (index)}
		<i class="fa-{index < filled ? 'solid' : 'regular'} fa-star"></i>
	{/each}
	{#if count !== undefined}
		<span>({count})</span>
	{/if}
</div>

<style>
	.rating-star {
		display: inline-flex;
		align-items: center;
		gap: 2px;
		color: #ff8a00;
	}

	.rating-star.rating-sm {
		font-size: 12px;
	}

	.rating-star.rating-md {
		font-size: 14px;
	}

	.rating-star.rating-lg {
		font-size: 18px;
	}

	.rating-star span {
		margin-left: 6px;
		color: #666;
		font-size: 13px;
	}
</style>
