import { get } from 'svelte/store';
import { apiDelete, apiGet, apiGetRaw, apiPost, apiPostForm, apiPut } from '$lib/utils/api';
import { isLoggedIn, setUser, user, type AuthUser } from '$lib/services/auth';

export interface ApiAddress {
	id: number;
	city?: string | null;
	town_village_district?: string | null;
	street_building_number?: string | null;
	unit_floor_apartment?: string | null;
	full_name?: string | null;
	contact_number?: string | number | null;
	is_default?: boolean;
	location_label?: string | null;
}

export type AddressPayload = Omit<ApiAddress, 'id'>;

/** Reloads the signed-in user (after a profile or avatar change). */
export async function refreshUser(): Promise<AuthUser | null> {
	if (!get(isLoggedIn)) return null;

	try {
		const res = await apiGetRaw<{ success?: boolean; user?: AuthUser; data?: AuthUser }>('/user/details');
		const userData = res?.user ?? res?.data;
		const current = get(user);
		const fresh: AuthUser = {
			id: userData?.id ?? current?.id ?? 0,
			name: userData?.name ?? current?.name ?? '',
			surname: (userData as any)?.surname ?? (current as any)?.surname ?? null,
			email: userData?.email ?? current?.email ?? null,
			phone: userData?.phone ?? current?.phone ?? null,
			avatar: userData?.avatar ?? current?.avatar ?? null
		};
		setUser(fresh);
		return fresh;
	} catch {
		return get(user);
	}
}

export async function uploadAvatar(file: File): Promise<string | null> {
	const formData = new FormData();
	formData.append('avatar', file);

	const res = await apiPostForm<{ avatar?: string; user?: { avatar?: string } }>('/user/change-avatar', formData);
	await refreshUser();
	return res?.avatar ?? res?.user?.avatar ?? null;
}

export async function removeAvatar(): Promise<void> {
	await apiDelete('/user/remove-avatar');
	await refreshUser();
}

/**
 * The profile is updated field by field (the API exposes one endpoint per
 * field), so only the values that actually changed are sent.
 */
export async function updateProfile(changes: {
	name?: string;
	surname?: string;
	email?: string;
	phone?: string;
}): Promise<void> {
	const current = get(user) as (AuthUser & { surname?: string | null }) | null;
	const changed = (next?: string, previous?: string | null) =>
		!!next && next.trim() !== (previous ?? '').trim();

	// Only what actually changed: the endpoints validate uniqueness, so resending
	// the same email/phone would be rejected as "already taken".
	if (changed(changes.name, current?.name)) {
		await apiPut('/user/change-name', { name: changes.name });
	}
	if (changed(changes.surname, current?.surname)) {
		await apiPut('/user/change-surname', { surname: changes.surname });
	}
	if (changed(changes.email, current?.email)) {
		await apiPut('/user/change-email', { email: changes.email });
	}
	// Phone changes require an OTP code, so they are not part of this form.

	await refreshUser();
}

export async function fetchAddresses(): Promise<ApiAddress[]> {
	const list = await apiGet<ApiAddress[]>('/user/address');
	return Array.isArray(list) ? list : [];
}

export async function createAddress(payload: AddressPayload): Promise<void> {
	const sanitized = {
		...payload,
		contact_number: Number(String(payload.contact_number).replace(/\D/g, '')) || 0
	};
	await apiPost('/user/address', sanitized);
}

export async function updateAddress(id: number, payload: Partial<AddressPayload>): Promise<void> {
	const sanitized = { ...payload };
	if (sanitized.contact_number !== undefined) {
		sanitized.contact_number = Number(String(sanitized.contact_number).replace(/\D/g, '')) || 0;
	}
	await apiPut(`/user/address/${id}`, sanitized);
}

export async function setDefaultAddress(id: number): Promise<void> {
	await updateAddress(id, { is_default: true });
}

export async function deleteAddress(id: number): Promise<void> {
	await apiDelete(`/user/address/${id}`);
}

