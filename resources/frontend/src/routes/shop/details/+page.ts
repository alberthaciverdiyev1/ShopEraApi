import { apiGet } from '$lib/utils/api';
import type { ApiProduct } from '$lib/services/products';
import type { PageLoad } from './$types';

export const load: PageLoad = async ({ url }) => {
	const id = url.searchParams.get('id');

	if (!id) {
		return { product: null, error: 'No product selected.' };
	}

	try {
		const product = await apiGet<ApiProduct>(`/product/${id}`);
		return { product, error: null };
	} catch (e) {
		return { product: null, error: e instanceof Error ? e.message : 'Failed to load the product' };
	}
};
