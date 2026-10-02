<script lang="ts">
	export interface SelectOption {
		value: string;
		label: string;
	}

	let {
		value = '',
		options = [],
		placeholder = '',
		onchange,
		class: className = ''
	}: {
		value?: string;
		options?: Array<SelectOption | string>;
		placeholder?: string;
		onchange?: (value: string) => void;
		class?: string;
	} = $props();

	const normalized = $derived(
		options.map((option) =>
			typeof option === 'string' ? { value: option, label: option } : option
		)
	);
</script>

<div class={`select-control ${className}`.trim()}>
	<select {value} onchange={(event) => onchange?.(event.currentTarget.value)}>
		{#if placeholder}
			<option value="">{placeholder}</option>
		{/if}
		{#each normalized as option (option.value)}
			<option value={option.value}>{option.label}</option>
		{/each}
	</select>
	<i class="fa-regular fa-chevron-down" aria-hidden="true"></i>
</div>

<style>
	.select-control {
		position: relative;
		display: inline-flex;
		align-items: center;
		min-width: 164px;
		height: 46px;
		border: 1px solid #dfe5ee;
		border-radius: 12px;
		background: #ffffff;
		box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
		transition: border-color 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
	}

	.select-control:hover {
		border-color: color-mix(in srgb, var(--theme) 28%, #dfe5ee);
		background: #fbfdfc;
	}

	.select-control:focus-within {
		border-color: color-mix(in srgb, var(--theme) 50%, #dfe5ee);
		box-shadow: 0 0 0 4px color-mix(in srgb, var(--theme) 10%, transparent);
	}

	select {
		width: 100%;
		height: 100%;
		padding: 0 42px 0 16px;
		border: 0;
		border-radius: inherit;
		background: transparent;
		color: #0f172a;
		font: inherit;
		font-size: 14px;
		font-weight: 650;
		line-height: 1;
		letter-spacing: 0;
		outline: 0;
		cursor: pointer;
		appearance: none;
		-webkit-appearance: none;
	}

	i {
		position: absolute;
		right: 15px;
		top: 50%;
		transform: translateY(-50%);
		color: #64748b;
		font-size: 12px;
		pointer-events: none;
		transition: color 0.16s ease;
	}

	.select-control:hover i,
	.select-control:focus-within i {
		color: var(--theme);
	}

	@media (max-width: 575.98px) {
		.select-control {
			min-width: 138px;
			height: 42px;
		}

		select {
			padding-inline-start: 13px;
			font-size: 13px;
		}
	}
</style>
