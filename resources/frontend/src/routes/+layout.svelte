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
	import SiteHeader from '$lib/components/layout/SiteHeader.svelte';
	import SubscriptionNotice from '$lib/components/layout/SubscriptionNotice.svelte';
	import { loadFeatures, features } from '$lib/services/features';
	import { loadSettings } from '$lib/services/settings';
	import MobileBottomNav from '$lib/components/layout/MobileBottomNav.svelte';
	import FloatingLiveChat from '$lib/components/layout/FloatingLiveChat.svelte';
	import SiteFooter from '$lib/components/layout/SiteFooter.svelte';

	let { children } = $props();

	onMount(() => {
		loadFeatures();
		loadSettings();
		let dispose: (() => void) | undefined;

		(async () => {
			// Loaded lazily: these libraries touch `window` at import time and
			// must not run during server-side rendering.
			const { Fancybox } = await import('@fancyapps/ui');

			Fancybox.bind('.popup-video, .img-popup');
			dispose = () => Fancybox.destroy();
		})();

		initAuth();
		initLocale();
		loadBasket();
		loadFavorites();
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

<MouseCursor />
<BackToTop />
{#if $features.chat !== false}
<FloatingLiveChat />
{/if}
<SiteHeader />
<SubscriptionNotice />
<MobileBottomNav />

{@render children()}

<SiteFooter />
