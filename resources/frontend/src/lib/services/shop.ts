import { apiGet, apiGetWithMeta } from '$lib/utils/api';
import { cachedGet } from '$lib/utils/api-cache';
import type { ApiProduct } from '$lib/services/products';

export interface ShopMeta {
	current_page: number;
	last_page: number;
	per_page: number;
	total: number;
}

export interface ShopFilters {
	search?: string;
	category_ids?: number[];
	brand_ids?: number[];
	color_ids?: number[];
	size_ids?: number[];
	price_min?: number;
	price_max?: number;
	order_by?: string;
	order_type?: 'asc' | 'desc';
	page?: number;
	per_page?: number;
	/** Dynamic filters: { filterId: [value, ...] } -> `filters[<id>][]=value`. */
	filters?: Record<string, string[]>;
	/** Dependent filter tree values: `filter_value_ids[]=…`. */
	filter_value_ids?: Array<string | number>;
}

export interface ShopResult {
	items: ApiProduct[];
	meta: ShopMeta;
}

const EMPTY_META: ShopMeta = { current_page: 1, last_page: 1, per_page: 24, total: 0 };

/** Storefront product list. `GET /api/product` answers `{success, message, data, meta}`. */
export async function fetchShopProducts(filters: ShopFilters = {}): Promise<ShopResult> {
	const query: Record<string, string | number | boolean | Array<string | number>> = { per_page: 24 };
	const { filters: dynamicFilters, ...rest } = filters;

	for (const [key, value] of Object.entries(rest)) {
		if (value === undefined || value === null || value === '') continue;
		if (Array.isArray(value)) {
			if (value.length) query[key] = value;
		} else {
			query[key] = value as string | number | boolean;
		}
	}

	for (const [filterId, values] of Object.entries(dynamicFilters ?? {})) {
		if (values?.length) query[`filters[${filterId}]`] = values;
	}

	const { data, meta } = await apiGetWithMeta<ApiProduct[]>('/product', query);
	return {
		items: Array.isArray(data) ? data : [],
		meta: { ...EMPTY_META, ...((meta as unknown as ShopMeta) ?? {}) }
	};
}

export interface ApiBrand {
	id: number;
	name: string;
	image?: string | null;
}

export async function fetchBrands(): Promise<ApiBrand[]> {
	const brands = await cachedGet<ApiBrand[]>('/brand');
	return Array.isArray(brands) ? brands : [];
}

export interface FacetItem {
	id: number;
	name?: string;
	hex?: string | null;
	image?: string | null;
	products_count?: number;
}

export interface ShopFacets {
	brands: FacetItem[];
	colors: FacetItem[];
	sizes: FacetItem[];
	price: { min: number; max: number };
	total: number;
}

const EMPTY_FACETS: ShopFacets = {
	brands: [],
	colors: [],
	sizes: [],
	price: { min: 0, max: 0 },
	total: 0
};

/** Facets for the sidebar — scoped to a category (and its children) when given. */
export async function fetchShopFilters(categoryIds: number[] = []): Promise<ShopFacets> {
	const query = categoryIds.length ? { category_ids: categoryIds } : undefined;
	const facets = await cachedGet<ShopFacets>('/product/filters', query);
	return { ...EMPTY_FACETS, ...(facets ?? {}) };
}
