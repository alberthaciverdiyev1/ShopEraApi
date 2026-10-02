import { derived, get, writable } from 'svelte/store';
import { apiDelete, apiGet, apiPost, apiPut } from '$lib/utils/api';
import { isLoggedIn } from '$lib/services/auth';
import type { ApiProduct } from '$lib/services/products';

export interface BasketItem {
	id: number;
	product_id: number;
	quantity: number;
	size_id?: number | null;
	color_id?: number | null;
	retail_unit_price?: number | null;
	retail_total?: number | null;
	product?: ApiProduct | null;
	color?: { id?: number; name?: string; hex?: string | null } | null;
	size?: { id?: number; name?: string } | null;
}

export const basketItems = writable<BasketItem[]>([]);
export const basketLoading = writable(false);

export const basketCount = derived(basketItems, (items) =>
	items.reduce((total, item) => total + (Number(item.quantity) || 0), 0)
);

/** Product ids already in the basket, for card "in cart" states. */
export const basketProductIds = derived(
	basketItems,
	(items) => new Set(items.map((item) => item.product_id))
);

export const basketTotal = derived(basketItems, (items) =>
	items.reduce((total, item) => total + (Number(item.retail_total) || 0), 0)
);

/** Loads the signed-in user's basket; clears it when signed out. */
export async function loadBasket(): Promise<void> {
	if (!get(isLoggedIn)) {
		basketItems.set([]);
		return;
	}

	basketLoading.set(true);
	try {
		const items = await apiGet<BasketItem[]>('/basket');
		basketItems.set(Array.isArray(items) ? items : []);
	} catch {
		basketItems.set([]);
	} finally {
		basketLoading.set(false);
	}
}

export async function addToBasket(
	productId: number,
	quantity = 1,
	sizeId?: number | null,
	colorId?: number | null
): Promise<void> {
	await apiPost('/basket', {
		product_id: productId,
		quantity,
		...(sizeId ? { size_id: sizeId } : {}),
		...(colorId ? { color_id: colorId } : {})
	});
	await loadBasket();
}

export async function updateBasketItem(id: number, quantity: number): Promise<void> {
	await apiPut(`/basket/${id}`, { quantity });
	await loadBasket();
}

export async function removeBasketItem(id: number): Promise<void> {
	await apiDelete(`/basket/${id}`);
	await loadBasket();
}
