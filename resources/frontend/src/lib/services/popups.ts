import { apiGet } from '$lib/utils/api';

export interface ApiPopup {
	id: number;
	type?: 'image' | 'video' | string;
	image?: string | null;
	video?: string | null;
	show_on_home_page?: boolean;
}

/** The active home popup, or null when none is configured. */
export async function fetchPopup(): Promise<ApiPopup | null> {
	try {
		const popup = await apiGet<ApiPopup>('/popup/show-one');
		return popup && (popup.image || popup.video) ? popup : null;
	} catch {
		return null;
	}
}
