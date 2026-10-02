<script lang="ts">
	import type { Snippet } from 'svelte';

	/** A single row in a radio / checkbox filter list. */
	let {
		checked = false,
		label = '',
		control = 'checkbox',
		count,
		onclick,
		children
	}: {
		checked?: boolean;
		label?: string;
		control?: 'checkbox' | 'radio';
		count?: number;
		onclick?: () => void;
		children?: Snippet;
	} = $props();
</script>

<li>
	<a href="#!" class:active={checked}
	   role={control === 'radio' ? 'radio' : 'checkbox'}
	   aria-checked={checked}
	   onclick={(event) => { event.preventDefault(); onclick?.(); }}>
		<span class="text">
			{#if children}
				{@render children()}
			{:else}
				<i class="fa-{checked ? 'solid' : 'regular'} fa-{control === 'checkbox' ? 'square-check' : 'circle-dot'}"></i>
			{/if}
			{label}
		</span>
		{#if count !== undefined}
			<span>{count}</span>
		{/if}
	</a>
</li>
