import { apiGet } from '$lib/utils/api';

export interface ApiSubscription {
	status?: string | null;
	plan?: string | null;
	ends_at?: string | null;
	usable?: boolean;
	past_due?: boolean;
	blocked?: boolean;
}

export async function fetchSubscription(): Promise<ApiSubscription> {
	try {
		const data = await apiGet<ApiSubscription>('/subscription');
		return data && typeof data === 'object' ? data : { usable: true };
	} catch {
		return { usable: true };
	}
}
