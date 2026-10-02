import { fetchPromoBlocks } from '$lib/services/promoBlocks';

export async function load({ fetch }) {
	return {
		promoBlocks: await fetchPromoBlocks(fetch)
	};
}
