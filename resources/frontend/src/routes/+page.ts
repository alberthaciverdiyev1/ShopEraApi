import { fetchFeatures, type ApiFeatures } from '$lib/services/features';
import { fetchFeaturedReviews } from '$lib/services/reviews';
import { fetchPromoBlocks } from '$lib/services/promoBlocks';
import { fetchBanners } from '$lib/services/banners';
import { fetchProducts } from '$lib/services/products';
import { fetchRecentBlogs } from '$lib/services/blog';

/**
 * Streaming load: promises are returned (not awaited) so SvelteKit can send the
 * page shell immediately and stream each section in as its API responds. The
 * home page renders skeleton loaders meanwhile.
 */
export function load({ fetch }: { fetch: typeof globalThis.fetch }) {
	const list = <T>(promise: Promise<T[]>): Promise<T[]> => promise.catch(() => []);
	const products = (orderBy: string, perPage: number, extra: Record<string, unknown> = {}) =>
		list(fetchProducts({ order_by: orderBy, order_type: 'desc', per_page: perPage, ...extra }, fetch));

	return {
		features: fetchFeatures(fetch).catch((): ApiFeatures => ({})),
		featuredReviews: list(fetchFeaturedReviews(fetch)),
		promoBlocks: fetchPromoBlocks(fetch),
		heroBanners: list(fetchBanners('big', fetch)),
		promoBanners: list(fetchBanners('small', fetch)),
		middleBanners: list(fetchBanners('middle', fetch)),
		latestProducts: products('created_at', 8),
		popularProducts: products('sales_count', 8),
		saleProducts: products('created_at', 8, { discount: 1 }),
		featuredProducts: products('created_at', 12),
		recentBlogs: list(fetchRecentBlogs(4, fetch))
	};
}
