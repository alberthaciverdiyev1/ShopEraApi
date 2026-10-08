import { apiGet, apiPostForm } from '$lib/utils/api';

export interface ApiVendor {
	id: number;
	name: Record<string, string> | string | null;
	slug: string;
	logo?: string | null;
	logo_url?: string | null;
	description?: Record<string, string> | string | null;
	phone?: string | null;
	email?: string | null;
	address?: string | null;
	status?: string;
	listings_count?: number;
}

function label(value: Record<string, string> | string | null | undefined, locale = 'az'): string {
	if (!value) return '';
	if (typeof value === 'string') return value;
	return value[locale] ?? value.az ?? value.en ?? Object.values(value)[0] ?? '';
}

export const vendorName = (vendor: ApiVendor, locale = 'az') => label(vendor.name, locale);
export const vendorDescription = (vendor: ApiVendor, locale = 'az') => label(vendor.description, locale);

/** The signed-in user's store (null if they have none). */
export async function fetchMyVendor(): Promise<ApiVendor | null> {
	try {
		return await apiGet<ApiVendor | null>('/vendor');
	} catch {
		return null;
	}
}

export async function fetchVendor(slug: string): Promise<ApiVendor> {
	return apiGet<ApiVendor>(`/vendors/${slug}`);
}

export async function createVendor(form: FormData): Promise<ApiVendor> {
	return apiPostForm<ApiVendor>('/vendor', form);
}

export async function updateVendor(form: FormData): Promise<ApiVendor> {
	form.append('_method', 'PUT');
	return apiPostForm<ApiVendor>('/vendor', form);
}

export function buildVendorForm(fields: Record<string, string>, logo?: File | null): FormData {
	const form = new FormData();
	for (const [key, value] of Object.entries(fields)) {
		if (value !== undefined && value !== null && `${value}` !== '') form.append(key, `${value}`);
	}
	if (logo) form.append('logo', logo);
	return form;
}
