import { apiGet, apiPost } from '$lib/utils/api';

type Fetcher = typeof fetch;

export interface ApiProductImage {
	id?: number;
	image_path?: string | null;
	color_id?: number | null;
}

export interface ApiProductFilter {
	id?: number;
	filter_id?: number;
	name: string;
	title?: string;
	value: string;
}

export interface ApiProduct {
	id: number;
	title: string;
	description?: string;
	price: number;
	discount: number;
	rate?: number | null;
	rate_count?: number;
	images?: ApiProductImage[];
	category?: { id: number; name: string } | null;
	brand?: { id: number; name: string } | null;
	city?: string | null;
	condition?: 'new' | 'used' | string | null;
	has_delivery?: boolean;
	is_promoted?: boolean;
	seller_type?: 'guest' | 'user' | 'vendor' | null;
	contact_name?: string | null;
	seller?: { id: number; name: string; slug: string; rating_avg?: number; rating_count?: number } | null;
	/** Dependent filter values chosen when the listing was posted. */
	filter_values?: Array<{
		filter_id?: number;
		filter?: string | null;
		filter_value_id?: number;
		value?: string | null;
	}>;
	sales_count?: number;
	views?: number;
	stock_count?: number;
	sku?: string;
	slug?: string | null;
	weight?: number | null;
	gender?: string | null;
	is_favorite?: boolean;
	is_subscribe?: boolean;
	is_new?: boolean;
	filters?: ApiProductFilter[];
	specifications?: Record<string, string>;
	colors?: { id: number; name?: string; hex?: string | null }[];
	sizes?: { id: number; name?: string; price?: number }[];
	reviews?: {
		id: number;
		rate?: number;
		comment?: string;
		created_at?: string;
		user?: { id?: number; name?: string } | null;
	}[];
}

const FALLBACK_IMAGE = '/assets/images/top-deals-item/topDealsItemThumb1_1.png';

export function productImage(product: ApiProduct): string {
	return product.images?.find((item) => item.image_path)?.image_path || FALLBACK_IMAGE;
}

export function productTitle(product: ApiProduct): string {
	return product.title ?? '';
}

export function productUrl(product: ApiProduct): string {
	return product.slug ? `/elan/${product.slug}` : `/shop/details?id=${product.id}`;
}

export function hasDiscount(product: ApiProduct): boolean {
	const price = Number(product.price);
	const discount = Number(product.discount);
	return discount > 0 && discount < price;
}

export function price(product: ApiProduct): number {
	return hasDiscount(product) ? Number(product.discount) : Number(product.price);
}

/**
 * `GET /api/product` answers with its own envelope (`{success, message, data, meta}`),
 * so `apiGet` already unwraps the product array for us.
 */
export async function fetchProducts(
	params: Record<string, string | number | boolean | Array<string | number>> = {},
	fetcher?: Fetcher
): Promise<ApiProduct[]> {
	const products = await apiGet<ApiProduct[]>('/product', { per_page: 12, ...params }, fetcher);
	return Array.isArray(products) ? products : [];
}

/** Personalised when signed in, popular otherwise. */
export async function fetchRecommendedProducts(perPage = 8, fetcher?: Fetcher): Promise<ApiProduct[]> {
	const products = await apiGet<ApiProduct[]>('/product/recommend', { per_page: perPage }, fetcher);
	return Array.isArray(products) ? products : [];
}

/** Back-in-stock notification for a product. */
export async function subscribeToStock(productId: number): Promise<void> {
	await apiPost('/product/subscribe', { product_id: productId });
}

export async function unsubscribeFromStock(productId: number): Promise<void> {
	await apiPost('/product/unsubscribe', { product_id: productId });
}
