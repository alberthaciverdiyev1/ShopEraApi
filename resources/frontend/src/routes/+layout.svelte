<script lang="ts">
	import { onMount } from 'svelte';
	import { afterNavigate } from '$app/navigation';
	import { initPageBehaviors } from '$lib/theme/behaviors';
	import favicon from '$lib/assets/favicon.svg';
	import '@fancyapps/ui/dist/fancybox/fancybox.css';
	import { initAuth } from '$lib/services/auth';
	import { loadBasket } from '$lib/services/basket';
	import { loadFavorites } from '$lib/services/favorites';
	import { applyThemeColors } from '$lib/services/theme-colors';
	import { initLocale } from '$lib/i18n';

	import MouseCursor from '$lib/components/layout/MouseCursor.svelte';
	import BackToTop from '$lib/components/layout/BackToTop.svelte';
	import NavProgress from '$lib/components/layout/NavProgress.svelte';
	import SiteHeader from '$lib/components/layout/SiteHeader.svelte';
	import SubscriptionNotice from '$lib/components/layout/SubscriptionNotice.svelte';
	import { loadFeatures, features } from '$lib/services/features';
	import { loadSettings } from '$lib/services/settings';
	import { startVersionWatch } from '$lib/utils/api-cache';
	import MobileBottomNav from '$lib/components/layout/MobileBottomNav.svelte';
	import FloatingLiveChat from '$lib/components/layout/FloatingLiveChat.svelte';
	import SiteFooter from '$lib/components/layout/SiteFooter.svelte';

	let { children } = $props();

	onMount(() => {
		// Feature flags decide which stores may hit the API — resolve them first
		// so disabled features never fire a request (no needless DB queries).
		loadFeatures().then(() => {
			loadBasket();
			loadFavorites();
		});
		// Apply the admin-managed favicon, falling back to the static one.
		// Update both rel="icon" and rel="shortcut icon" (browsers may prefer
		// either) and bust the browser's favicon cache with a version query.
		loadSettings().then((s) => {
			if (!s.favicon_url) return;

			const href = `${s.favicon_url}${s.favicon_url.includes('?') ? '&' : '?'}v=${Date.now()}`;
			const links = document.querySelectorAll<HTMLLinkElement>('link[rel="icon"], link[rel="shortcut icon"]');

			if (links.length === 0) {
				const link = document.createElement('link');
				link.rel = 'icon';
				document.head.appendChild(link);
			}

			document.querySelectorAll<HTMLLinkElement>('link[rel="icon"], link[rel="shortcut icon"]').forEach((link) => {
				link.href = href;
			});
		});
		let dispose: (() => void) | undefined;

		(async () => {
			// Loaded lazily: these libraries touch `window` at import time and
			// must not run during server-side rendering.
			const { Fancybox } = await import('@fancyapps/ui');

			Fancybox.bind('.popup-video, .img-popup');
			dispose = () => Fancybox.destroy();
		})();

		startVersionWatch();
		initAuth();
		initLocale();
		applyThemeColors();
		applyBackgrounds();
		initPageBehaviors();

		return () => dispose?.();
	});

	// The theme marks background images with data-bg-src / data-mask-src
	// (its main.js used to apply them on load).
	function applyBackgrounds() {
		document.querySelectorAll<HTMLElement>('[data-bg-src]').forEach((el) => {
			const src = el.getAttribute('data-bg-src');
			if (!src) return;
			el.style.backgroundImage = `url(${src})`;
			el.classList.add('background-image');
			el.removeAttribute('data-bg-src');
		});

		document.querySelectorAll<HTMLElement>('[data-mask-src]').forEach((el) => {
			const src = el.getAttribute('data-mask-src');
			if (!src) return;
			el.style.maskImage = `url(${src})`;
			el.style.webkitMaskImage = `url(${src})`;
			el.classList.add('bg-mask');
			el.removeAttribute('data-mask-src');
		});
	}

	afterNavigate(() => {
		startVersionWatch();
		initAuth();
		loadBasket();
		loadFavorites();
		applyThemeColors();
		applyBackgrounds();
		initPageBehaviors();
	});
</script>

<svelte:head>
	<link rel="icon" href={favicon} />
	<link rel="shortcut icon" href="/assets/images/favicon.png" />
	<link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="/assets/css/all.min.css" />
	<link rel="stylesheet" href="/assets/css/icomoon-one.css" />
	<link rel="stylesheet" href="/assets/css/animate.css" />
	<link rel="stylesheet" href="/assets/css/splitting.css" />
	<link rel="stylesheet" href="/assets/css/magnific-popup.css" />
	<link rel="stylesheet" href="/assets/css/meanmenu.css" />
	<link rel="stylesheet" href="/assets/css/nice-select.css" />
	<link rel="stylesheet" href="/assets/css/main.css" />
	<link rel="stylesheet" href="/assets/css/snaker-theme.css" />
</svelte:head>

<NavProgress />
<MouseCursor />
<BackToTop />
{#if $features.chat}
<FloatingLiveChat />
{/if}
<SiteHeader />
<SubscriptionNotice />
<MobileBottomNav />

{@render children()}

<SiteFooter />
