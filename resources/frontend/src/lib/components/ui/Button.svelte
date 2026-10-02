<script lang="ts">
	import type { Snippet } from 'svelte';

	type Variant = 'primary' | 'secondary' | 'dark' | 'ghost';

	let {
		href,
		variant = 'primary',
		type = 'button',
		class: className = '',
		onclick,
		children,
		...rest
	}: {
		href?: string;
		variant?: Variant;
		type?: 'button' | 'submit' | 'reset';
		class?: string;
		onclick?: (event: MouseEvent | SubmitEvent) => void;
		children: Snippet;
		[key: string]: unknown;
	} = $props();

	const VARIANTS: Record<Variant, string> = {
		primary: 'theme-btn',
		secondary: 'theme-btn style7',
		dark: 'theme-btn bg-red-2',
		ghost: 'theme-btn style7 border-0'
	};

	const classes = $derived(`${VARIANTS[variant] ?? VARIANTS.primary} ${className}`.trim());
</script>

{#if href}
	<a {href} class={classes} {onclick} {...rest}>{@render children()}</a>
{:else}
	<button {type} class={classes} {onclick} {...rest}>{@render children()}</button>
{/if}
