<script lang="ts">
	const CLASSES = {
		default: 'badge',
		off: 'best-seller-product-items-two__badge1',
		offer: 'offer'
	};

	let { text = '', variant = 'default' }: { text?: string; variant?: keyof typeof CLASSES } = $props();
</script>

{#if text}
	<div class={CLASSES[variant] ?? CLASSES.default}>{text}</div>
{/if}
