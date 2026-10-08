import { loadCategories } from '$lib/services/categories';

export const ssr = true;
export const prerender = false;

// Load the category tree during SSR and on the first client navigation, so the
// navbar mega-menu is populated on first paint instead of after hydration.
export async function load() {
	await loadCategories();
	return {};
}
