import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-fade';

/**
 * Svelte action that turns an element carrying `data-slider-options` into a
 * Swiper instance, mirroring the theme's global slider behaviour:
 * `.slider-prev` / `.slider-next` as arrows, `.slider-pagination` as bullets.
 *
 * Swiper modules are only registered when the slider actually uses them —
 * loading Pagination without a pagination element throws.
 */
export function slider(node: HTMLElement) {
	let instance: Swiper | null = null;

	const raw = node.getAttribute('data-slider-options');
	let opts: Record<string, any> = {};
	try {
		opts = raw ? JSON.parse(raw) : {};
	} catch {
		opts = {};
	}

	const prevEl = node.querySelector('.slider-prev') as HTMLElement | null;
	const nextEl = node.querySelector('.slider-next') as HTMLElement | null;
	const pagEl = node.querySelector('.slider-pagination') as HTMLElement | null;

	const modules = [Autoplay, EffectFade];
	const config: Record<string, any> = {
		slidesPerView: 1,
		spaceBetween: opts.spaceBetween ?? 24,
		loop: opts.loop !== false,
		speed: opts.speed ?? 1000,
		effect: opts.effect ?? 'slide',
		centeredSlides: !!opts.centeredSlides,
		autoplay:
			opts.autoplay === false
				? false
				: opts.autoplay || { delay: 6000, disableOnInteraction: false },
		...opts,
	};

	if (prevEl || nextEl) {
		modules.push(Navigation);
		config.navigation = { prevEl, nextEl };
	}

	if (pagEl) {
		modules.push(Pagination);
		config.pagination = { el: pagEl, clickable: true, type: opts.paginationType || 'bullets' };
	}

	config.modules = modules;

	instance = new Swiper(node, config);

	return {
		destroy() {
			instance?.destroy(true, true);
			instance = null;
		},
	};
}
