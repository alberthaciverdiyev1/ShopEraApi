import { apiGet, apiGetWithMeta, apiPost, apiPostForm, apiDelete } from '$lib/utils/api';

export type SellerType = 'guest' | 'user' | 'vendor';

export interface ApiListing {
	id: number;
	title: Record<string, string> | null;
	description: Record<string, string> | null;
	price: string | number | null;
	seller_type: SellerType;
	condition: string | null;
	has_delivery?: boolean;
	category_id: number | null;
	brand_id?: number | null;
	brand?: string | null;
	model?: string | null;
	city_id: number | null;
	city?: string | null;
	vendor?: { id: number; name: Record<string, string> | string; slug: string } | null;
	contact_name?: string | null;
	contact_phone?: string | null;
	contact_email?: string | null;
	is_promoted?: boolean;
	is_premium?: boolean;
	images?: string[] | null;
	created_at?: string | null;
}

export function listingTitle(listing: ApiListing, locale = 'az'): string {
	return listing.title?.[locale] ?? listing.title?.az ?? listing.title?.en ?? '';
}

export function listingDescription(listing: ApiListing, locale = 'az'): string {
	return listing.description?.[locale] ?? listing.description?.az ?? listing.description?.en ?? '';
}

export function listingImage(listing: ApiListing, index = 0): string {
	return listing.images?.[index] ?? '/assets/images/shop/shop-01.png';
}

export async function fetchMyListings(): Promise<ApiListing[]> {
	const { data } = await apiGetWithMeta<ApiListing[]>('/listings/mine');
	return Array.isArray(data) ? data : [];
}

/** Guest listing — returns the secret manage URL. */
export async function createGuestListing(
	form: FormData
): Promise<{ listing: ApiListing; manage_url: string }> {
	return apiPostForm<{ listing: ApiListing; manage_url: string }>('/listings/guest', form);
}

/** Signed-in seller listing (user/vendor). */
export async function createListing(form: FormData): Promise<{ listing: ApiListing }> {
	return apiPostForm<{ listing: ApiListing }>('/listings', form);
}

export async function fetchManagedListing(token: string): Promise<ApiListing> {
	return apiGet<ApiListing>(`/listings/manage/${token}`);
}

export async function updateManagedListing(token: string, form: FormData): Promise<ApiListing> {
	form.append('_method', 'PUT');
	return apiPostForm<ApiListing>(`/listings/manage/${token}`, form);
}

export async function deleteManagedListing(token: string): Promise<void> {
	await apiDelete(`/listings/manage/${token}`);
}

export async function deleteListing(id: string | number): Promise<void> {
	await apiDelete(`/listings/${id}`);
}

/** Builds a FormData payload from a listing form + optional image files. */
export function buildListingForm(
	fields: Record<string, string | number | Array<string | number>>,
	files: File[] = []
): FormData {
	const form = new FormData();
	for (const [key, value] of Object.entries(fields)) {
		if (Array.isArray(value)) {
			for (const item of value) {
				if (item !== undefined && item !== null && `${item}` !== '') form.append(`${key}[]`, `${item}`);
			}
		} else if (value !== undefined && value !== null && `${value}` !== '') {
			form.append(key, `${value}`);
		}
	}
	for (const file of files) {
		form.append('images[]', file);
	}
	return form;
}

export interface FieldConfig {
	visible: boolean;
	required: boolean;
}
export type ListingSchema = Record<string, FieldConfig>;

/** Per-category listing form schema (which fields show / are required). */
export async function loadListingFields(categoryId: number): Promise<ListingSchema> {
	try {
		return await apiGet<ListingSchema>('/listings/fields', { category_id: categoryId });
	} catch {
		return {};
	}
}

export interface ListingContact {
	name?: string | null;
	phone?: string | null;
	email?: string | null;
}

/** Reveal the seller's contact details for a listing. */
export async function fetchListingContact(id: number): Promise<ListingContact> {
	return apiGet<ListingContact>(`/listings/${id}/contact`);
}

export type ReportReason = 'spam' | 'fraud' | 'wrong_category' | 'offensive' | 'duplicate' | 'other';

export interface ReportPayload {
	reason: ReportReason;
	comment?: string;
}

/** Report a listing (guest or signed-in). */
export async function reportListing(id: number, payload: ReportPayload): Promise<void> {
	await apiPost(`/listings/${id}/report`, { reason: payload.reason, comment: payload.comment });
}

export interface PromotionPackage {
	id: number;
	name: Record<string, string> | string | null;
	type: 'promoted' | 'premium' | string;
	days: number;
	price: string | number;
}

export function packageName(pkg: PromotionPackage, locale = 'az'): string {
	if (!pkg.name) return '';
	if (typeof pkg.name === 'string') return pkg.name;
	return pkg.name[locale] ?? pkg.name.az ?? Object.values(pkg.name)[0] ?? '';
}

/** Active promotion packages. */
export async function fetchPromotionPackages(): Promise<PromotionPackage[]> {
	try {
		const list = await apiGet<PromotionPackage[]>('/listings/promotion-packages');
		return Array.isArray(list) ? list : [];
	} catch {
		return [];
	}
}

/** Create a promotion order for a listing (activated after payment). */
export async function promoteListing(id: number, packageId: number): Promise<void> {
	await apiPost(`/listings/${id}/promote`, { package_id: packageId });
}
