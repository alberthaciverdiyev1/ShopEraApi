<script lang="ts">
	let {
		current = 1,
		lastPage = 1,
		onChange
	}: {
		current?: number;
		lastPage?: number;
		onChange?: (page: number) => void;
	} = $props();

	const window = $derived.by(() => {
		const size = 5;
		let start = Math.max(1, current - Math.floor(size / 2));
		const end = Math.min(lastPage, start + size - 1);
		start = Math.max(1, end - size + 1);
		return Array.from({ length: end - start + 1 }, (_, index) => start + index);
	});

	const pad = (value: number) => String(value).padStart(2, '0');

	function go(target: number) {
		if (target < 1 || target > lastPage || target === current) return;
		onChange?.(target);
	}
</script>

{#if lastPage > 1}
	<div class="pagination">
		{#if current > 1}
			<a href="#!" class="prev" onclick={(event) => { event.preventDefault(); go(current - 1); }}>
				<i class="fa-solid fa-chevron-left"></i>
			</a>
		{/if}
		{#each window as pageNumber (pageNumber)}
			<a href="#!" class="page" class:active={pageNumber === current}
			   onclick={(event) => { event.preventDefault(); go(pageNumber); }}>{pad(pageNumber)}</a>
		{/each}
		{#if current < lastPage}
			<a href="#!" class="next" onclick={(event) => { event.preventDefault(); go(current + 1); }}>
				<i class="fa-solid fa-chevron-right"></i>
			</a>
		{/if}
	</div>
{/if}
