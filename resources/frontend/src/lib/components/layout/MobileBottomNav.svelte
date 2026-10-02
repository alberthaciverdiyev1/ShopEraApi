<script lang="ts">
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { isLoggedIn, logout, user } from '$lib/services/auth';
	import { basketCount } from '$lib/services/basket';
	import { favoritesCount } from '$lib/services/favorites';
	import { languageOptions, locale, setLocale, translate, type Locale } from '$lib/i18n';

	type NavItem = {
		href: string;
		label: string;
		icon: string;
		match: (path: string) => boolean;
		badge?: number;
	};

	const path = $derived(page.url.pathname);
	const items = $derived<NavItem[]>([
		{
			href: '/',
			label: $translate('Home'),
			icon: 'fa-solid fa-house',
			match: (current) => current === '/'
		},
		{
			href: '/categories',
			label: $translate('Catalog'),
			icon: 'fa-solid fa-grid-2',
			match: (current) => current.startsWith('/categories')
		},
		{
			href: '/wishlist',
			label: $translate('Wishlist'),
			icon: 'fa-solid fa-heart',
			match: (current) => current.startsWith('/wishlist'),
			badge: $isLoggedIn ? $favoritesCount : 0
		},
		{
			href: '/cart',
			label: $translate('Cart'),
			icon: 'fa-solid fa-cart-shopping',
			match: (current) => current.startsWith('/cart') || current.startsWith('/checkout'),
			badge: $isLoggedIn ? $basketCount : 0
		}
	]);

	const drawerLinks = [
		{ href: '/', labelKey: 'Home', icon: 'fa-solid fa-house' },
		{ href: '/shop', labelKey: 'Shop', icon: 'fa-solid fa-store' },
		{ href: '/about', labelKey: 'About', icon: 'fa-solid fa-circle-info' },
		{ href: '/contact', labelKey: 'Contact', icon: 'fa-solid fa-headset' }
	];

	let hidden = $state(false);
	let accountDrawerOpen = $state(false);
	const accountActive = $derived(
		path.startsWith('/settings') || path.startsWith('/login') || path.startsWith('/dashboard')
	);

	function closeAccountDrawer() {
		accountDrawerOpen = false;
	}

	async function navigateFromDrawer(href: string) {
		closeAccountDrawer();
		await goto(href);
	}

	async function handleLogout() {
		await logout();
		closeAccountDrawer();
		await goto('/');
	}

	onMount(() => {
		let lastScrollY = window.scrollY;
		let ticking = false;

		const handleScroll = () => {
			if (!ticking) {
				window.requestAnimationFrame(() => {
					const currentScrollY = window.scrollY;
					const diff = currentScrollY - lastScrollY;

					if (Math.abs(diff) > 8) {
						if (currentScrollY > 80 && diff > 0) {
							// Scrolling down -> hide smoothly
							hidden = true;
						} else if (diff < 0 || currentScrollY <= 80) {
							// Scrolling up or near top -> show
							hidden = false;
						}
						lastScrollY = currentScrollY;
					}
					ticking = false;
				});
				ticking = true;
			}
		};

		window.addEventListener('scroll', handleScroll, { passive: true });
		const handleKeydown = (event: KeyboardEvent) => {
			if (event.key === 'Escape') closeAccountDrawer();
		};
		window.addEventListener('keydown', handleKeydown);
		return () => {
			window.removeEventListener('scroll', handleScroll);
			window.removeEventListener('keydown', handleKeydown);
		};
	});
</script>

<nav class="mobile-bottom-nav" class:nav-hidden={hidden} aria-label="Mobile primary navigation">
	{#each items as item (item.href)}
		<a class:active={item.match(path)} href={item.href} aria-label={item.label}>
			<span class="icon-wrap">
				<i class={item.icon}></i>
				{#if item.badge}
					<em>{item.badge > 99 ? '99+' : item.badge}</em>
				{/if}
			</span>
			<span>{item.label}</span>
		</a>
	{/each}
	<button
		type="button"
		class:active={accountActive || accountDrawerOpen}
		aria-label={$translate('Account')}
		aria-expanded={accountDrawerOpen}
		aria-controls="mobile-account-drawer"
		onclick={() => (accountDrawerOpen = true)}
	>
		<span class="icon-wrap">
			<i class="fa-solid fa-bars"></i>
		</span>
		<span>{$translate('Account')}</span>
	</button>
</nav>

{#if accountDrawerOpen}
	<button
		type="button"
		class="drawer-backdrop"
		aria-label={$translate('Close')}
		onclick={closeAccountDrawer}
	></button>
	<div
		id="mobile-account-drawer"
		class="account-drawer"
		aria-label={$translate('Account')}
		aria-modal="true"
		role="dialog"
	>
		<div class="drawer-handle"></div>
		<div class="drawer-header">
			<div>
				<span>{$translate('Account')}</span>
				<strong>{$isLoggedIn ? $user?.name ?? $translate('My account') : $translate('Guest account')}</strong>
			</div>
			<button type="button" aria-label={$translate('Close')} onclick={closeAccountDrawer}>
				<i class="fa-solid fa-xmark"></i>
			</button>
		</div>

		<nav class="drawer-pages" aria-label={$translate('Menu')}>
			{#each drawerLinks as link (link.href)}
				<button type="button" class:active={path === link.href || (link.href !== '/' && path.startsWith(link.href))} onclick={() => navigateFromDrawer(link.href)}>
					<i class={link.icon}></i>
					<span>{$translate(link.labelKey)}</span>
				</button>
			{/each}
		</nav>

		<div class="drawer-language">
			<label for="mobile-drawer-language">{$translate('Language')}</label>
			<select
				id="mobile-drawer-language"
				value={$locale}
				onchange={(event) => setLocale(event.currentTarget.value as Locale)}
			>
				{#each $languageOptions as language (language.value)}
					<option value={language.value}>{language.label}</option>
				{/each}
			</select>
		</div>

		<div class="drawer-actions">
			{#if $isLoggedIn}
				<button type="button" onclick={() => navigateFromDrawer('/dashboard')}>
					<i class="fa-solid fa-user"></i>
					<span>{$translate('My account')}</span>
				</button>
				<button type="button" onclick={() => navigateFromDrawer('/order/history')}>
					<i class="fa-solid fa-receipt"></i>
					<span>{$translate('Orders')}</span>
				</button>
				<button type="button" onclick={() => navigateFromDrawer('/settings')}>
					<i class="fa-solid fa-gear"></i>
					<span>{$translate('Settings')}</span>
				</button>
			{:else}
				<button type="button" onclick={() => navigateFromDrawer('/login')} class="primary-action">
					<i class="fa-solid fa-user"></i>
					<span>{$translate('Login')}</span>
				</button>
			{/if}
			<button type="button" onclick={() => navigateFromDrawer('/wishlist')}>
				<i class="fa-solid fa-heart"></i>
				<span>{$translate('Wishlist')}</span>
				{#if $isLoggedIn && $favoritesCount > 0}
					<small>{$favoritesCount}</small>
				{/if}
			</button>
			<button type="button" onclick={() => navigateFromDrawer('/cart')}>
				<i class="fa-solid fa-cart-shopping"></i>
				<span>{$translate('Cart')}</span>
				{#if $isLoggedIn && $basketCount > 0}
					<small>{$basketCount}</small>
				{/if}
			</button>
		</div>

		{#if $isLoggedIn}
			<div class="drawer-actions drawer-danger">
				<button type="button" onclick={handleLogout}>
					<i class="fa-solid fa-arrow-right-from-bracket"></i>
					<span>{$translate('Logout')}</span>
				</button>
			</div>
		{/if}
	</div>
{/if}

<style>
	.mobile-bottom-nav {
		position: fixed;
		right: 0;
		bottom: 0;
		left: 0;
		z-index: 999;
		display: none;
		grid-template-columns: repeat(5, minmax(0, 1fr));
		align-items: center;
		min-height: 60px;
		padding: 5px 6px calc(5px + env(safe-area-inset-bottom));
		border-top: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 0;
		background: rgba(255, 255, 255, 0.98);
		box-shadow: 0 -8px 28px rgba(15, 23, 42, 0.12);
		backdrop-filter: blur(14px);
		transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.35s ease;
		will-change: transform, opacity;
	}

	.mobile-bottom-nav.nav-hidden {
		transform: translateY(calc(100% + 28px));
		opacity: 0;
		pointer-events: none;
	}

	:global(body.shop-mobile-filter-open) .mobile-bottom-nav {
		transform: translateY(calc(100% + 28px));
		opacity: 0;
		pointer-events: none;
	}

	.mobile-bottom-nav > a,
	.mobile-bottom-nav > button {
		position: relative;
		display: inline-flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 4px;
		min-width: 0;
		color: #96989f;
		font-size: 10px;
		font-weight: 600;
		line-height: 1;
		text-decoration: none;
		border: 0;
		background: transparent;
		padding: 0;
	}

	.mobile-bottom-nav i {
		font-size: 19px;
		line-height: 1;
	}

	.mobile-bottom-nav > a[href="/wishlist"] i,
	.mobile-bottom-nav > a[href="/cart"] i {
		font-size: 21px;
	}

	.mobile-bottom-nav > a.active,
	.mobile-bottom-nav > button.active {
		color: #e91e79;
	}

	.mobile-bottom-nav > a.active i,
	.mobile-bottom-nav > button.active i {
		transform: translateY(-1px);
	}

	.icon-wrap {
		position: relative;
		display: inline-flex;
	}

	em {
		position: absolute;
		top: -9px;
		right: -12px;
		min-width: 16px;
		height: 16px;
		padding: 0 4px;
		border: 2px solid #fff;
		border-radius: 999px;
		background: #ef3e2e;
		color: #fff;
		font-size: 9px;
		font-style: normal;
		line-height: 12px;
		text-align: center;
	}

	.drawer-backdrop {
		position: fixed;
		inset: 0;
		z-index: 1000;
		display: none;
		border: 0;
		background: rgba(15, 23, 42, 0.36);
		backdrop-filter: blur(4px);
	}

	.account-drawer {
		position: fixed;
		right: 0;
		bottom: 0;
		left: 0;
		z-index: 1001;
		display: none;
		padding: 10px 16px calc(18px + env(safe-area-inset-bottom));
		border-radius: 24px 24px 0 0;
		background: #ffffff;
		box-shadow: 0 -18px 42px rgba(15, 23, 42, 0.18);
	}

	.drawer-handle {
		width: 44px;
		height: 4px;
		margin: 0 auto 14px;
		border-radius: 999px;
		background: #d7dce6;
	}

	.drawer-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 14px;
		margin-bottom: 14px;
	}

	.drawer-header span {
		display: block;
		color: #8b94a6;
		font-size: 12px;
		font-weight: 700;
		line-height: 1;
	}

	.drawer-header strong {
		display: block;
		margin-top: 5px;
		color: #111827;
		font-size: 18px;
		font-weight: 800;
		line-height: 1.2;
	}

	.drawer-header button {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 38px;
		height: 38px;
		border: 1px solid #e8edf5;
		border-radius: 50%;
		background: #f8fafc;
		color: #17213b;
	}

	.drawer-language {
		display: grid;
		grid-template-columns: 1fr auto;
		align-items: center;
		gap: 12px;
		margin-bottom: 12px;
		padding: 12px;
		border: 1px solid #edf1f7;
		border-radius: 16px;
		background: #f8fafc;
	}

	.drawer-language label {
		color: #17213b;
		font-size: 13px;
		font-weight: 800;
	}

	.drawer-language select {
		min-width: 116px;
		height: 36px;
		padding: 0 10px;
		border: 1px solid #dbe3ef;
		border-radius: 999px;
		background: #fff;
		color: #17213b;
		font-size: 13px;
		font-weight: 700;
		outline: 0;
	}

	.drawer-actions {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 10px;
	}

	.drawer-actions button {
		position: relative;
		display: flex;
		align-items: center;
		gap: 9px;
		min-height: 48px;
		padding: 0 12px;
		border: 1px solid #edf1f7;
		border-radius: 16px;
		background: #ffffff;
		color: #17213b;
		font-size: 13px;
		font-weight: 800;
		text-align: start;
	}

	.drawer-actions i {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 30px;
		height: 30px;
		border-radius: 50%;
		background: #f0f7ff;
		color: #0ea5e9;
		font-size: 14px;
		flex: 0 0 auto;
	}

	.drawer-actions .primary-action {
		grid-column: 1 / -1;
		border-color: rgba(233, 30, 121, 0.2);
		background: #fff3f8;
		color: #d9166c;
	}

	.drawer-actions .primary-action i {
		background: #e91e79;
		color: #fff;
	}

	.drawer-actions small {
		margin-inline-start: auto;
		min-width: 18px;
		height: 18px;
		padding: 0 5px;
		border-radius: 999px;
		background: #ef3e2e;
		color: #fff;
		font-size: 10px;
		line-height: 18px;
		text-align: center;
	}

	@media (max-width: 1199.98px) {
		.mobile-bottom-nav {
			display: grid;
		}

		.drawer-backdrop,
		.account-drawer {
			display: block;
		}

		:global(.back-to-top) {
			bottom: 76px !important;
		}
	}

	@media (max-width: 420px) {
		.mobile-bottom-nav {
			min-height: 58px;
		}

		.mobile-bottom-nav > a,
		.mobile-bottom-nav > button {
			font-size: 9px;
		}
	}

	.drawer-pages {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 8px;
		margin-bottom: 12px;
		padding: 4px;
		border: 1px solid #edf1f7;
		border-radius: 18px;
		background: #f8fafc;
	}

	.drawer-pages button {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 7px;
		width: 100%;
		min-height: 74px;
		padding: 10px 6px;
		border: 1px solid transparent;
		border-radius: 14px;
		background: transparent;
		text-align: center;
		font-size: 11px;
		font-weight: 800;
		line-height: 1.15;
		color: #334155;
		transition:
			background 0.2s ease,
			border-color 0.2s ease,
			color 0.2s ease,
			transform 0.2s ease;
	}

	.drawer-pages button i {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 34px;
		height: 34px;
		border-radius: 12px;
		background: #ffffff;
		box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
		color: #94a3b8;
		font-size: 14px;
		transition:
			background 0.2s ease,
			color 0.2s ease,
			box-shadow 0.2s ease;
	}

	.drawer-pages button:hover,
	.drawer-pages button.active {
		border-color: color-mix(in srgb, var(--theme) 22%, #ffffff);
		background: #ffffff;
		color: #0f172a;
		transform: translateY(-1px);
	}

	.drawer-pages button:hover i,
	.drawer-pages button.active i {
		background: var(--theme);
		color: #ffffff;
		box-shadow: 0 10px 20px color-mix(in srgb, var(--theme) 24%, transparent);
	}

	.drawer-danger {
		margin-top: 8px;
		border-top: 1px solid #e5e7eb;
		padding-top: 8px;
	}
	.drawer-danger button {
		color: #dc2626;
	}
</style>
