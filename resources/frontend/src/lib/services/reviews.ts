import { apiGet, apiPost } from '$lib/utils/api';
import { featureEnabled } from '$lib/services/features';

type Fetcher = typeof fetch;

export interface ApiReview {
	id: number;
	rate?: number;
	comment?: string;
	created_at?: string;
	user?: { id?: number; name?: string } | null;
}

export async function fetchProductReviews(productId: number, fetcher?: Fetcher): Promise<ApiReview[]> {
	if (!featureEnabled('reviews')) return [];

	const reviews = await apiGet<ApiReview[]>(`/review/${productId}`, {}, fetcher);
	return Array.isArray(reviews) ? reviews : [];
}

/** A signed-in shopper reviews a product (1–5 stars, optional comment). */
export async function createReview(productId: number, rate: number, comment: string): Promise<void> {
	if (!featureEnabled('reviews')) return;

	await apiPost('/review', {
		product_id: productId,
		rate,
		comment: comment.trim() || null
	});
}

export interface ApiFeaturedReview {
	id: number;
	rate?: number;
	comment?: string | null;
	created_at?: string;
	user?: { id?: number | null; name?: string | null; avatar?: string | null } | null;
	product?: { id?: number | null; title?: string | null; image?: string | null } | null;
}

/** Reviews the admin marked for the home "What our client say" strip. */
export async function fetchFeaturedReviews(fetcher?: Fetcher): Promise<ApiFeaturedReview[]> {
	if (!featureEnabled('reviews')) return [];

	const reviews = await apiGet<ApiFeaturedReview[]>('/review/featured', {}, fetcher);
	return Array.isArray(reviews) ? reviews : [];
}
