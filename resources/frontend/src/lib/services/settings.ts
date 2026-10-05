import { writable } from 'svelte/store';
import { apiGet } from '$lib/utils/api';

export interface ApiStoreSettings {
	id?: number;
	instagram_url?: string | null;
	tiktok_url?: string | null;
	whatsapp_number?: string | null;
	email?: string | null;
	google_map_url?: string | null;
	phone_number_1?: string | null;
	phone_number_2?: string | null;
	phone_number_3?: string | null;
	phone_number_4?: string | null;
	address?: string | null;
	logo_url?: string | null;
	favicon_url?: string | null;
	facebook_url?: string | null;
	twitter_url?: string | null;
	youtube_url?: string | null;
	telegram_url?: string | null;
	linkedin_url?: string | null;
}

type SettingsPayload =	| ApiStoreSettings	| ApiStoreSettings[]	| { data?: ApiStoreSettings | ApiStoreSettings[] }	| null	| undefined;

export const fallbackSettings: ApiStoreSettings = {
	email: 'support@snaker.store',
	address: 'Bakı, Azərbaycan',
	logo_url: null,
	favicon_url: null,
	phone_number_1: '+994 (12) 555-00-00',
	instagram_url: 'https://instagram.com/snaker.store',
	tiktok_url: 'https://tiktok.com/@snaker.store',
	whatsapp_number: '994709990569',
	google_map_url: null,
	facebook_url: 'https://facebook.com/snaker.store',
	twitter_url: 'https://x.com/snaker_az',
	youtube_url: 'https://youtube.com/@snaker',
	telegram_url: 'https://t.me/snaker_az',
	linkedin_url: 'https://linkedin.com/company/snaker'
};

export const settings = writable<ApiStoreSettings>(fallbackSettings);

function clean(value: string | null | undefined): string {
	return typeof value === 'string' ? value.trim() : '';
}

function firstSettings(payload: SettingsPayload): ApiStoreSettings | null {
	if (!payload) return null;
	if (Array.isArray(payload)) return payload[0] ?? null;
	const candidate = payload as Record<string, unknown>;
	if ('data' in candidate) {
		const data = candidate.data as ApiStoreSettings | ApiStoreSettings[] | undefined;
		if (Array.isArray(data)) return data[0] ?? null;
		return data ?? null;
	}
	return payload as ApiStoreSettings;
}

export function normalizeSettings(payload: SettingsPayload): ApiStoreSettings {
	return {
		...fallbackSettings,
		...(firstSettings(payload) ?? {})
	};
}

export async function loadSettings(fetcher?: typeof fetch): Promise<ApiStoreSettings> {
	try {
		const payload = await apiGet<SettingsPayload>('/setting', {}, fetcher);
		const nextSettings = normalizeSettings(payload);
		settings.set(nextSettings);
		return nextSettings;
	} catch {
		settings.set(fallbackSettings);
		return fallbackSettings;
	}
}

export function settingPhones(value: ApiStoreSettings): string[] {
	return [
		value.phone_number_1,
		value.phone_number_2,
		value.phone_number_3,
		value.phone_number_4,
		value.whatsapp_number
	]
		.map(clean)
		.filter((phone, index, list) => phone && list.indexOf(phone) === index);
}

export function primaryPhone(value: ApiStoreSettings): string {
	return settingPhones(value)[0] ?? clean(fallbackSettings.phone_number_1);
}

export function phoneHref(phone: string): string {
	const compact = phone.replace(/[^\d+]/g, '');
	return compact ? `tel:${compact}` : '#';
}

export function mailHref(email: string): string {
	return email ? `mailto:${email}` : '#';
}

export function settingEmail(value: ApiStoreSettings): string {
	return clean(value.email) || clean(fallbackSettings.email);
}

export function settingAddress(value: ApiStoreSettings): string {
	return clean(value.address) || clean(fallbackSettings.address);
}

export function settingSocialLinks(value: ApiStoreSettings) {
	const whatsapp = clean(value.whatsapp_number) || clean(fallbackSettings.whatsapp_number);
	const whatsappHref = whatsapp ? `https://wa.me/${whatsapp.replace(/\D/g, '')}` : '';

	return [
		{ label: 'Instagram', icon: 'fa-brands fa-instagram', href: clean(value.instagram_url) || clean(fallbackSettings.instagram_url) },
		{ label: 'TikTok', icon: 'fa-brands fa-tiktok', href: clean(value.tiktok_url) || clean(fallbackSettings.tiktok_url) },
		{ label: 'WhatsApp', icon: 'fa-brands fa-whatsapp', href: whatsappHref },
		{ label: 'Facebook', icon: 'fa-brands fa-facebook-f', href: clean(value.facebook_url) || clean(fallbackSettings.facebook_url) },
		{ label: 'X (Twitter)', icon: 'fa-brands fa-x-twitter', href: clean(value.twitter_url) || clean(fallbackSettings.twitter_url) },
		{ label: 'Telegram', icon: 'fa-brands fa-telegram', href: clean(value.telegram_url) || clean(fallbackSettings.telegram_url) },
		{ label: 'YouTube', icon: 'fa-brands fa-youtube', href: clean(value.youtube_url) || clean(fallbackSettings.youtube_url) },
		{ label: 'LinkedIn', icon: 'fa-brands fa-linkedin-in', href: clean(value.linkedin_url) || clean(fallbackSettings.linkedin_url) }
	].filter((item) => item.href);
}
