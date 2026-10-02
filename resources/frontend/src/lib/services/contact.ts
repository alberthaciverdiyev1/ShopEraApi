import { apiGet, apiPost } from '$lib/utils/api';

export interface ApiContactInfo {
	address: string;
	email: string;
	phone: string | null;
	phones: string[];
	whatsapp_number: string | null;
	google_map_url: string | null;
	instagram_url: string | null;
	tiktok_url: string | null;
	twitter_url?: string | null;
	facebook_url?: string | null;
	telegram_url?: string | null;
	youtube_url?: string | null;
	linkedin_url?: string | null;
}

export interface ContactMessagePayload {
	first_name: string;
	last_name?: string;
	email: string;
	phone?: string;
	subject?: string;
	message: string;
}

export async function fetchContactInfo(fetcher?: typeof fetch): Promise<ApiContactInfo> {
	try {
		const data = await apiGet<ApiContactInfo>('/contact', {}, fetcher);
		return data ?? {
			address: 'Bakı, Azərbaycan',
			email: 'support@shopera.az',
			phone: null,
			phones: [],
			whatsapp_number: null,
			google_map_url: null,
			instagram_url: null,
			tiktok_url: null
		};
	} catch (error) {
		console.error('Failed to load contact info:', error);
		return {
			address: 'Bakı, Azərbaycan',
			email: 'support@shopera.az',
			phone: null,
			phones: [],
			whatsapp_number: null,
			google_map_url: null,
			instagram_url: null,
			tiktok_url: null
		};
	}
}

export async function sendContactMessage(payload: ContactMessagePayload): Promise<{ id: number }> {
	return apiPost<{ id: number }>('/contact', payload as unknown as Record<string, unknown>);
}
