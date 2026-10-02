<script lang="ts">
	import { filterChoices, filterTitle, type FilterDefinition } from '$lib/services/filters';
	import Select from '$lib/components/ui/Select.svelte';
	import SearchInput from '$lib/components/ui/SearchInput.svelte';
	import Choice from '$lib/components/ui/Choice.svelte';

	let {
		filter,
		selected = [],
		open = false,
		ontoggle,
		onchange
	}: {
		filter: FilterDefinition;
		selected?: string[];
		open?: boolean;
		ontoggle?: () => void;
		onchange: (values: string[]) => void;
	} = $props();

	const choices = $derived(filterChoices(filter));
	const isChecked = (value: string) => selected.includes(value);

	function toggle(value: string) {
		onchange(isChecked(value) ? selected.filter((item) => item !== value) : [...selected, value]);
	}

	function setSingle(value: string) {
		onchange(value ? [value] : []);
	}
</script>

<!-- Dynamic filter: the control follows `type` (select / radio / checkbox / input / range) -->
<div class="single-sidebar-widget">
	<button
		type="button"
		class="sidebar-widget-header"
		onclick={ontoggle}
		aria-expanded={open}
	>
		<h3 class="single-sidebar-widget__wid-title--title">{filterTitle(filter)}</h3>
		{#if selected.length}
			<span class="active-filter-badge">{selected.length}</span>
		{/if}
		<i class="fa-solid fa-chevron-down widget-collapse-icon" class:rotated={open}></i>
	</button>

	{#if open}
		<div class="sidebar-widget-body">
			{#if filter.type === 'select'}
				<Select value={selected[0] ?? ''} options={choices} placeholder="All" onchange={setSingle} />
			{:else if filter.type === 'input' || filter.type === 'text' || filter.type === 'number'}
				<SearchInput value={selected[0] ?? ''} placeholder="Type a value" onSubmit={setSingle} />
			{:else if filter.type === 'range'}
				{@const val = Number(selected[0] ?? 30)}
				{@const pct = Math.min(100, Math.max(0, Math.round((val / 30) * 100)))}
				<div class="single-sidebar-widget__filter-price-widget-categories">
					<div class="range-slider">
						<input type="range" class="form-range"
							   min={0} max={30} step={1}
							   value={val}
							   style="--slider-pct: {pct}%; background: linear-gradient(90deg, var(--theme, #1570ef) {pct}%, #e0e0e0 {pct}%);"
							   onchange={(event) => setSingle(event.currentTarget.value)}>
						<div class="range-values">
							<span>0</span><span>{val}</span><span>30</span>
						</div>
					</div>
				</div>
			{:else}
				<div class="single-sidebar-widget__widget-categories">
					<ul>
						{#each choices as choice (choice)}
							<Choice
								checked={isChecked(choice)}
								label={choice}
								control={filter.type === 'radio' ? 'radio' : 'checkbox'}
								onclick={() => (filter.type === 'radio' ? setSingle(choice) : toggle(choice))}
							/>
						{/each}
					</ul>
				</div>
			{/if}
		</div>
	{/if}
</div>

<style>
	.sidebar-widget-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		background: transparent;
		border: none;
		padding: 0;
		margin: 0;
		cursor: pointer;
		text-align: left;
	}

	.sidebar-widget-header .single-sidebar-widget__wid-title--title {
		margin-bottom: 0;
		font-size: 15px !important;
		font-weight: 600 !important;
		line-height: 1.3 !important;
		color: var(--title, #0a111e);
	}

	.sidebar-widget-body {
		margin-top: 15px;
	}

	.widget-collapse-icon {
		font-size: 12px;
		color: var(--border-5, #575757);
		transition: transform 0.25s ease, color 0.25s ease;
	}

	.sidebar-widget-header:hover .widget-collapse-icon,
	.sidebar-widget-header:hover .single-sidebar-widget__wid-title--title {
		color: var(--theme, #ed0006);
	}

	.widget-collapse-icon.rotated {
		transform: rotate(180deg);
	}

	.active-filter-badge {
		background-color: var(--theme, #ed0006);
		color: #fff;
		font-size: 11px;
		font-weight: 600;
		border-radius: 10px;
		padding: 2px 7px;
		margin-left: auto;
		margin-right: 10px;
		line-height: 1;
	}
</style>
