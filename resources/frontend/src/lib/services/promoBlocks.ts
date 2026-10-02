import { apiGet } from '$lib/utils/api';

type Fetcher = typeof fetch;

export interface ApiPromoBlock {
	id: number;
	type: 'offer' | 'ad' | string;
	title?: string | null;
	subtitle?: string | null;
	description?: string | null;
	image?: string | null;
	button_text?: string | null;
	url?: string | null;
	badge?: string | null;
}

/** Offer/ad blocks from Manager.ShopEra (Free plan only). */
export async function fetchPromoBlocks(fetcher?: Fetcher): Promise<ApiPromoBlock[]> {
	try {
		const blocks = await apiGet<ApiPromoBlock[]>('/promo-blocks', {}, fetcher);
		return Array.isArray(blocks) ? blocks : [];
	} catch {
		return [];
	}
}
