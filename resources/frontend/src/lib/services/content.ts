import { apiGet } from '$lib/utils/api';

export interface ApiFaq {
	id: number;
	title: string | Record<string, string>;
	description: string | Record<string, string>;
	type?: string;
}

export interface ApiLegalTerm {
	id: number;
	type: string;
	html: string | Record<string, string>;
}

/** `?type=` defaults to `main_page`; `register_page` holds the terms of service. */
export async function fetchLegalTerm(type = 'main_page'): Promise<ApiLegalTerm | null> {
	const terms = await apiGet<ApiLegalTerm[]>('/legal-terms', { type });
	return Array.isArray(terms) && terms.length ? terms[0] : null;
}

export async function fetchFaqs(type?: string): Promise<ApiFaq[]> {
	const faqs = await apiGet<ApiFaq[]>('/faq', type ? { type } : {});
	return Array.isArray(faqs) ? faqs : [];
}

/** Translatable fields may arrive as a plain string or as a `{ az, en, ... }` map. */
export function localized(value: string | Record<string, string> | undefined, locale = 'az'): string {
	if (!value) return '';
	if (typeof value === 'string') return value;
	return value[locale] ?? Object.values(value)[0] ?? '';
}
