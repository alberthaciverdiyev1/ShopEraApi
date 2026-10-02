import { apiGet } from '$lib/utils/api';

/** Keys of the numeric limits defined in Manager.ShopEra. */
export interface ApiPlanLimits {
	max_products?: number | null;
	max_categories?: number | null;
	max_staff?: number | null;
	max_orders?: number | null;
	storage_gb?: number | null;
}

/** Current consumption; same keys as the limits, so they pair one-to-one. */
export type ApiPlanUsage = ApiPlanLimits;

export interface ApiPlan {
	plan?: string | null;
	status?: string | null;
	ends_at?: string | null;
	usable?: boolean;
	limits: ApiPlanLimits;
	usage: ApiPlanUsage;
}

/** The plan's limit keys in the order the dashboard should render them. */
export const PLAN_LIMIT_KEYS: Array<keyof ApiPlanLimits> = [
	'max_products',
	'max_categories',
	'max_staff',
	'max_orders',
	'storage_gb'
];

export const PLAN_LIMIT_LABELS: Record<keyof ApiPlanLimits, string> = {
	max_products: 'Məhsullar',
	max_categories: 'Kateqoriyalar',
	max_staff: 'İşçilər',
	max_orders: 'Aylıq sifarişlər',
	storage_gb: 'Yaddaş (GB)'
};

export async function fetchPlan(fetcher?: typeof fetch): Promise<ApiPlan | null> {
	try {
		const data = await apiGet<ApiPlan>('/plan', {}, fetcher);
		if (!data || typeof data !== 'object') return null;

		return {
			plan: data.plan ?? null,
			status: data.status ?? null,
			ends_at: data.ends_at ?? null,
			usable: data.usable ?? true,
			limits: data.limits ?? {},
			usage: data.usage ?? {}
		};
	} catch {
		return null;
	}
}

/** Percentage of a limit used (0–100), or null when the limit is unlimited. */
export function limitPercent(used: number | null | undefined, limit: number | null | undefined): number | null {
	if (limit === null || limit === undefined || limit <= 0) return null;
	return Math.min(100, Math.round((Number(used ?? 0) / limit) * 100));
}
