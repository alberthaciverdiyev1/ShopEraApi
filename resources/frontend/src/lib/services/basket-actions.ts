import { get } from 'svelte/store';
import { goto } from '$app/navigation';
import { isLoggedIn } from '$lib/services/auth';
import { addToBasket } from '$lib/services/basket';

/** Adds a product to the basket, sending guests to the login page first. */
export async function addProductToCart(
	productId: number,
	quantity = 1,
	sizeId?: number | null,
	colorId?: number | null
): Promise<void> {
	if (!get(isLoggedIn)) {
		await goto('/login');
		return;
	}

	await addToBasket(productId, quantity, sizeId, colorId);
}
