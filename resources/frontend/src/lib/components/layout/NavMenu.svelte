<script lang="ts">
	import { onMount } from 'svelte';
	import { page } from '$app/state';
	import { translate } from '$lib/i18n';

	let { mobile = false } = $props();
	let root = $state<HTMLUListElement | null>(null);

	const pathname = $derived(page.url.pathname);

	function isItemActive(href: string): boolean {
		if (href === '/') {
			return pathname === '/';
		}
		return pathname === href || pathname.startsWith(`${href}/`);
	}

	const navLinks = [
		{ href: '/', labelKey: 'Home' },
		{ href: '/shop', labelKey: 'Shop' },
		{ href: '/about', labelKey: 'About' },
		// { href: '/blog', labelKey: 'Blog' },
		{ href: '/contact', labelKey: 'Contact' }
	];

	onMount(() => {
		root?.querySelectorAll(':scope > li > a[href="#"]').forEach((anchor) => {
			const link = anchor.parentElement?.querySelector('.submenu a[href]:not([href="#"])');
			if (link) anchor.setAttribute('href', link.getAttribute('href') ?? '#');
		});
	});

	// On the mobile copy the dropdowns open as an accordion instead of on hover.
	function handleClick(event: MouseEvent) {
		if (!mobile) return;
		const anchor = (event.target as HTMLElement)?.closest('a');
		const li = anchor?.parentElement;
		if (li) {
			event.preventDefault();
			li.classList.toggle('open');
		}
	}
</script>

<ul bind:this={root} class:mobile-nav={mobile} onclick={handleClick}>
	{#each navLinks as link (link.href)}
		{@const active = isItemActive(link.href)}
		<li class:active={active}>
			<a href={link.href} class:active={active}>
				{$translate(link.labelKey)}
			</a>
		</li>
	{/each}
</ul>

<style>
	/* Active navbar link styling */
	ul :global(li.active > a),
	ul :global(li > a.active) {
		color: var(--theme) !important;
		font-weight: 700;
	}

	ul.mobile-nav {
		list-style: none;
		margin: 0;
		padding: 0;
	}

	ul.mobile-nav :global(li > a) {
		display: block;
		padding: 12px 0;
		border-bottom: 1px solid #e5e7eb;
		color: #0a111e;
		font-weight: 600;
	}

	ul.mobile-nav :global(li.active > a),
	ul.mobile-nav :global(li > a.active) {
		color: var(--theme) !important;
		font-weight: 700 !important;
	}

	ul.mobile-nav :global(.submenu) {
		position: static !important;
		display: none !important;
		opacity: 1 !important;
		visibility: visible !important;
		box-shadow: none !important;
		padding-left: 16px !important;
	}

	ul.mobile-nav :global(li.open > .submenu) {
		display: block !important;
	}

	ul.mobile-nav :global(.has-homemenu .homemenu-items) {
		display: none;
	}

	/* Navbar links behave as plain links: no dropdown on hover. */
	:global(.header-main .main-menu ul li:hover > .submenu),
	:global(.header-main .main-menu ul li .submenu li:hover > .submenu) {
		visibility: hidden !important;
		opacity: 0 !important;
	}
</style>
