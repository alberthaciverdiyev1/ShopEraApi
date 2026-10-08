<script lang="ts">
	import { locale } from '$lib/i18n';
	import {
		filterTitle,
		filterValueTitle,
		type ApiFilterNode,
		type FilterValueNode
	} from '$lib/services/filters-tree';

	let {
		tree = [],
		selection = {},
		onchange
	}: {
		tree?: ApiFilterNode[];
		selection?: Record<number, number>;
		onchange: (filter: ApiFilterNode, valueId: number | null) => void;
	} = $props();

	/** Root values, or the children of the parent filter's selected value. */
	function options(filter: ApiFilterNode): FilterValueNode[] {
		const dep = filter.depends_on_filter_id;
		if (dep) {
			const parent = selection[dep];
			if (!parent) return [];
			return filter.values.filter((v) => Number(v.parent_value_id) === Number(parent));
		}
		return filter.values.filter((v) => !v.parent_value_id);
	}
</script>

{#if tree.length}
	<div class="shop-tree-filters">
		{#each tree as filter (filter.id)}
			{@const disabled = !!filter.depends_on_filter_id && !selection[filter.depends_on_filter_id]}
			<div class="shop-tree-filter">
				<label for={`tf-${filter.id}`}>
					{filterTitle(filter, $locale)}{#if filter.required} *{/if}
				</label>
				<select id={`tf-${filter.id}`} class="form-select" {disabled}
				        value={selection[filter.id] ?? ''}
				        onchange={(e) => onchange(filter, e.currentTarget.value ? Number(e.currentTarget.value) : null)}>
					<option value="">Hamısı</option>
					{#each options(filter) as value (value.id)}
						<option value={value.id}>{filterValueTitle(value, $locale)}</option>
					{/each}
				</select>
			</div>
		{/each}
	</div>
{/if}

<style>
	.shop-tree-filters { display: flex; flex-direction: column; gap: 12px; }
	.shop-tree-filter label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 700; color: #0f172a; }
	.shop-tree-filter select { border-radius: 10px; padding: 8px 12px; font-size: 14px; }
	.shop-tree-filter select:disabled { opacity: .55; }
</style>
