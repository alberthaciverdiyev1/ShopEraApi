import { derived, get, writable } from 'svelte/store';
import { apiGet } from '$lib/utils/api';
import { isLoggedIn } from '$lib/services/auth';
import { basketTotal } from '$lib/services/basket';

export interface PromoResult {
	id: number;
	code: string;
	discount_percent: number;
	original_price?: number;
	discounted_price?: number;
}

export const appliedPromo = writable<PromoResult | null>(null);

/** Discount for the basket as it stands now (quantities may change after applying). */
export const promoDiscount = derived([appliedPromo, basketTotal], ([$promo, $total]) =>
	$promo ? (Number($promo.discount_percent) / 100) * Number($total) : 0
);

export const finalTotal = derived([basketTotal, promoDiscount], ([$total, $discount]) =>
	Math.max(0, Number($total) - $discount)
);

/** Validates a code against the user's basket. */
export async function applyPromoCode(code: string): Promise<PromoResult> {
	if (!get(isLoggedIn)) {
		throw new Error('Please sign in to use a promo code.');
	}

	const trimmed = code.trim();
	if (!trimmed) throw new Error('Enter a promo code.');

	const result = await apiGet<PromoResult>(`/promo-code/check/${encodeURIComponent(trimmed)}`);
	appliedPromo.set(result);
	return result;
}

export function clearPromo(): void {
	appliedPromo.set(null);
}
