<script lang="ts">
	import { onMount } from 'svelte';

	onMount(() => {
		const inner = document.querySelector<HTMLElement>('.cursor-inner');
		const outer = document.querySelector<HTMLElement>('.cursor-outer');
		if (!inner || !outer) return;

		const move = (event: MouseEvent) => {
			const transform = `translate(${event.clientX}px, ${event.clientY}px)`;
			outer.style.transform = transform;
			inner.style.transform = transform;
		};

		const hoverOn = (event: Event) => {
			if ((event.target as HTMLElement)?.closest('a, .cursor-pointer')) {
				inner.classList.add('cursor-hover');
				outer.classList.add('cursor-hover');
			}
		};

		const hoverOff = (event: Event) => {
			if ((event.target as HTMLElement)?.closest('a, .cursor-pointer')) {
				inner.classList.remove('cursor-hover');
				outer.classList.remove('cursor-hover');
			}
		};

		window.addEventListener('mousemove', move);
		document.addEventListener('mouseover', hoverOn);
		document.addEventListener('mouseout', hoverOff);

		inner.style.visibility = 'visible';
		outer.style.visibility = 'visible';

		return () => {
			window.removeEventListener('mousemove', move);
			document.removeEventListener('mouseover', hoverOn);
			document.removeEventListener('mouseout', hoverOff);
		};
	});
</script>

<!-- Mouse Cursor Start -->
<div class="mouse-cursor cursor-outer"></div>
<div class="mouse-cursor cursor-inner"></div>
