import { writable } from 'svelte/store';
import { apiGet } from '$lib/utils/api';

type Fetcher = typeof fetch;

export interface ApiFeatures {
	online_payment?: boolean;
	cash_on_delivery?: boolean;
	whatsapp_orders?: boolean;
	phone_orders?: boolean;
	stories?: boolean;
	reviews?: boolean;
	blog?: boolean;
	banners?: boolean;
	chat?: boolean;
	favorites?: boolean;
	promo_codes?: boolean;
	pickup_points?: boolean;
	fast_delivery?: boolean;
    lang_az?: boolean;
    lang_en?: boolean;
    lang_ru?: boolean;
    lang_tr?: boolean;
	faq?: boolean;
	legal_terms?: boolean;
	contact_page?: boolean;
	referrals?: boolean;
	balance_wallet?: boolean;
	push_notifications?: boolean;
	notifications?: boolean;
	delivery_cities?: boolean;
	delivery_info?: boolean;
	delivery_prices?: boolean;
	products?: boolean;
	categories?: boolean;
	brands?: boolean;
	colors?: boolean;
	sizes?: boolean;
	product_filters?: boolean;
	basket?: boolean;
	addresses?: boolean;
	theme_colors?: boolean;
	custom_theme?: boolean;
	[key: string]: boolean | undefined;
}

/** Shared store; loaded once in +layout.svelte and read reactively everywhere. */
export const features = writable<ApiFeatures>({});

export async function loadFeatures(fetcher?: Fetcher): Promise<ApiFeatures> {
	try {
		const flags = await apiGet<ApiFeatures>('/features', {}, fetcher);
		const value = flags && typeof flags === 'object' ? flags : {};
		features.set(value);
		return value;
	} catch {
		features.set({});
		return {};
	}
}

/** Backwards-compatible one-shot fetch. */
export async function fetchFeatures(fetcher?: Fetcher): Promise<ApiFeatures> {
	return loadFeatures(fetcher);
}
