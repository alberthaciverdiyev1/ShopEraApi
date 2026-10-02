import { fetchFeaturedReviews } from '$lib/services/reviews';
import { fetchFeatures, type ApiFeatures } from '$lib/services/features';
import { fetchPromoBlocks } from '$lib/services/promoBlocks';
import { fetchBanners } from '$lib/services/banners';
import { fetchProducts } from '$lib/services/products';
import { fetchRecentBlogs } from '$lib/services/blog';

export async function load({ fetch }) {
	const features = await fetchFeatures(fetch).catch((): ApiFeatures => ({}));
	const bannersEnabled = features.banners !== false;

	const [
		featuredReviews,
		promoBlocks,
		heroBanners,
		promoBanners,
		middleBanners,
		latestProducts,
		popularProducts,
		saleProducts,
		featuredProducts,
		recentBlogs
	] = await Promise.all([
		fetchFeaturedReviews(fetch).catch(() => []),
		fetchPromoBlocks(fetch),
		bannersEnabled ? fetchBanners('big', fetch).catch(() => []) : [],
		bannersEnabled ? fetchBanners('small', fetch).catch(() => []) : [],
		bannersEnabled ? fetchBanners('middle', fetch).catch(() => []) : [],
		fetchProducts({ order_by: 'created_at', order_type: 'desc', per_page: 8 }, fetch).catch(() => []),
		fetchProducts({ order_by: 'sales_count', order_type: 'desc', per_page: 8 }, fetch).catch(() => []),
		fetchProducts({ discount: 1, order_by: 'created_at', order_type: 'desc', per_page: 8 }, fetch).catch(() => []),
		fetchProducts({ order_by: 'created_at', order_type: 'desc', per_page: 12 }, fetch).catch(() => []),
		fetchRecentBlogs(4, fetch).catch(() => [])
	]);

	return {
		featuredReviews,
		features,
		promoBlocks,
		heroBanners,
		promoBanners,
		middleBanners,
		latestProducts,
		popularProducts,
		saleProducts,
		featuredProducts,
		recentBlogs
	};
}
