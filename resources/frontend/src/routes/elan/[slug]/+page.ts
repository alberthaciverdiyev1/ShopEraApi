import { apiGet } from '$lib/utils/api';
import type { ApiProduct } from '$lib/services/products';
import type { PageLoad } from './$types';

export const load: PageLoad = async ({ params, fetch }) => {
	const slug = params.slug;

	try {
		const product = await apiGet<ApiProduct>(`/product/slug/${slug}`, {}, fetch);
		return { product, error: null };
	} catch (e) {
		return { product: null, error: e instanceof Error ? e.message : 'Failed to load the product' };
	}
};
