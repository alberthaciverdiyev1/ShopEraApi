import { apiGet } from '$lib/utils/api';

export interface ApiBrand {
	id: number;
	name: string;
	image?: string | null;
	is_active?: boolean;
	sort_order?: number;
}

let cache: ApiBrand[] | null = null;

/** Active brands for the listing form (loaded once). */
export async function loadBrands(force = false): Promise<ApiBrand[]> {
	if (cache && !force) return cache;

	try {
		const list = await apiGet<ApiBrand[]>('/brand');
		cache = (Array.isArray(list) ? list : []).filter((b) => b.is_active !== false);
	} catch {
		cache = [];
	}

	return cache;
}
