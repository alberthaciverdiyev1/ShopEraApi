import { apiGet } from '$lib/utils/api';
import type { ApiProduct } from '$lib/services/products';

type Fetcher = typeof fetch;

export type BannerType = 'big' | 'middle' | 'small';

export interface ApiBanner {
	id: number;
	image?: string | null;
	second_image?: string | null;
	type: BannerType | string;
	url?: string | null;
	title?: string | null;
	subtitle?: string | null;
	product_id?: number | null;
	is_active?: boolean;
	product?: ApiProduct | null;
}

export async function fetchBanners(type?: BannerType, fetcher?: Fetcher): Promise<ApiBanner[]> {
	const banners = await apiGet<ApiBanner[]>('/banner', type ? { type } : {}, fetcher);
	return Array.isArray(banners) ? banners : [];
}

export function bannerHref(banner: ApiBanner): string {
	if (banner.product?.id) return `/shop/details?id=${banner.product.id}`;
	return banner.url || '/shop';
}
