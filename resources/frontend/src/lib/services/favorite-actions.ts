import { get } from 'svelte/store';
import { goto } from '$app/navigation';
import { isLoggedIn } from '$lib/services/auth';
import { toggleFavorite } from '$lib/services/favorites';

/** Toggles a favourite, sending guests to the login page first. */
export async function toggleFavoriteProduct(productId: number): Promise<void> {
	if (!get(isLoggedIn)) {
		await goto('/login');
		return;
	}

	await toggleFavorite(productId);
}
