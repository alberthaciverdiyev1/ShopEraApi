import { derived, get, writable } from 'svelte/store';
import { apiDelete, apiGet, apiPost } from '$lib/utils/api';
import { isLoggedIn } from '$lib/services/auth';
import { featureEnabled } from '$lib/services/features';
import type { ApiProduct } from '$lib/services/products';


export const favoriteProducts = writable<ApiProduct[]>([]);
export const favoritesLoading = writable(false);

export const favoriteIds = derived(
	favoriteProducts,
	(items) => new Set(items.map((item) => item.id))
);

export const favoritesCount = derived(favoriteProducts, (items) => items.length);

export async function loadFavorites(): Promise<void> {
	if (!featureEnabled('favorites') || !get(isLoggedIn)) {
		favoriteProducts.set([]);
		return;
	}

	favoritesLoading.set(true);
	try {
		const items = await apiGet<ApiProduct[]>('/favorite');
		favoriteProducts.set(Array.isArray(items) ? items : []);
	} catch {
		favoriteProducts.set([]);
	} finally {
		favoritesLoading.set(false);
	}
}

/** `POST /favorite/{id}` toggles, so one call covers add and remove. */
export async function toggleFavorite(productId: number): Promise<void> {
	if (!featureEnabled('favorites')) return;

	await apiPost(`/favorite/${productId}`);
	await loadFavorites();
}

export async function removeFavorite(productId: number): Promise<void> {
	if (!featureEnabled('favorites')) return;

	await apiDelete(`/favorite/${productId}`);
	await loadFavorites();
}
