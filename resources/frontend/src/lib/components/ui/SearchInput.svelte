<script lang="ts">
	let {
		value = $bindable(''),
		placeholder = 'Search...',
		onSubmit
	}: {
		value?: string;
		placeholder?: string;
		onSubmit?: (value: string) => void;
	} = $props();
</script>

<div class="single-sidebar-widget__search-widget">
	<form onsubmit={(event) => {
		event.preventDefault();
		onSubmit?.(value);
	}}>
		<input type="text" {placeholder} bind:value>
		<button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
	</form>
</div>
