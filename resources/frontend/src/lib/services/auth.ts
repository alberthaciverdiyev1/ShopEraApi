import { derived, writable } from 'svelte/store';
import { apiGet, apiPost, TOKEN_KEY } from '$lib/utils/api';

export interface AuthUser {
	id: number;
	name: string;
	surname?: string | null;
	email?: string | null;
	phone?: string | null;
	avatar?: string | null;
}

const USER_KEY = 'snaker_user';

export const user = writable<AuthUser | null>(null);
export const isLoggedIn = derived(user, (value) => value !== null);

/**
 * Restores the session from localStorage.
 *
 * Runs at import time: page components mount *before* the layout's onMount,
 * so waiting for that would leave them with a null user on a fresh load.
 */
export function initAuth() {
	if (typeof localStorage === 'undefined') return;

	const stored = localStorage.getItem(USER_KEY);
	if (!stored) return;

	try {
		user.set(JSON.parse(stored));
	} catch {
		localStorage.removeItem(USER_KEY);
	}
}

// eager restore (browser only)
initAuth();

/** Replaces the cached user (after a profile update). */
export function setUser(authUser: AuthUser | null) {
	if (authUser) {
		localStorage.setItem(USER_KEY, JSON.stringify(authUser));
	} else {
		localStorage.removeItem(USER_KEY);
	}
	user.set(authUser);
}

function persist(token: string, authUser: AuthUser) {
	localStorage.setItem(TOKEN_KEY, token);
	localStorage.setItem(USER_KEY, JSON.stringify(authUser));
	user.set(authUser);
}

/** Login with either a phone number or an email address. */
export async function login(identifier: string, password: string): Promise<AuthUser> {
	const value = identifier.trim();
	const payload = value.includes('@') ? { email: value, password } : { phone: value, password };

	const data = await apiPost<{ token: string; user: AuthUser }>('/auth/login', payload);
	persist(data.token, data.user);
	return data.user;
}

export async function register(payload: {
	name: string;
	surname?: string;
	email?: string;
	phone: string;
	password: string;
}): Promise<AuthUser> {
	const data = await apiPost<{ token: string; user: AuthUser }>('/auth/register', payload);
	persist(data.token, data.user);
	return data.user;
}

export async function logout(): Promise<void> {
	try {
		await apiPost('/auth/logout');
	} catch {
		// the local session is cleared regardless
	}

	localStorage.removeItem(TOKEN_KEY);
	localStorage.removeItem(USER_KEY);
	user.set(null);
}
