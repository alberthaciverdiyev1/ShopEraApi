// Base URL of the Laravel API. In dev it goes through the Vite proxy (see vite.config.ts);
// in production it is served under the same origin.
import { browser } from '$app/environment';

// Browser → same-origin `/api` (nginx routes it to Laravel). During SSR the
// Node process cannot call its own /api, so it talks to Laravel directly.
const ssrBase =
	typeof process !== 'undefined' && process.env.INTERNAL_API_URL
		? process.env.INTERNAL_API_URL
		: 'http://snaker-web/api';

export const API_BASE = (import.meta.env.VITE_API_URL as string | undefined) || (browser ? '/api' : ssrBase);

type QueryValue = string | number | boolean | Array<string | number>;
type Query = Record<string, QueryValue>;
type Fetcher = typeof fetch;

function buildQuery(query: Query): string {
	const search = new URLSearchParams();
	for (const [key, value] of Object.entries(query)) {
		if (value === undefined || value === null) continue;
		// Laravel reads arrays as `key[]=a&key[]=b`.
		if (Array.isArray(value)) {
			value.forEach((item) => search.append(`${key}[]`, String(item)));
		} else {
			search.append(key, String(value));
		}
	}
	return search.toString();
}

export const TOKEN_KEY = 'snaker_token';

function authHeaders(): Record<string, string> {
	const token = typeof localStorage !== 'undefined' ? localStorage.getItem(TOKEN_KEY) : null;
	const headers: Record<string, string> = { Accept: 'application/json' };
	if (token) headers.Authorization = `Bearer ${token}`;
	return headers;
}

export class ApiError extends Error {
	status: number;

	constructor(message: string, status: number) {
		super(message);
		this.name = 'ApiError';
		this.status = status;
	}
}

async function request<T>(path: string, query: Query = {}, fetcher: Fetcher = fetch): Promise<T> {
	const qs = buildQuery(query);
	const url = `${API_BASE}${path}${qs ? `?${qs}` : ''}`;

	const response = await fetcher(url, { headers: authHeaders() });
	if (!response.ok) {
		throw new Error(`API ${response.status} on ${path}`);
	}

	return (await response.json()) as T;
}

/** GET an endpoint and unwrap the Laravel response envelope to just `data`. */
export async function apiGet<T>(path: string, query: Query = {}, fetcher?: Fetcher): Promise<T> {
	const json = await request<{ data?: T }>(path, query, fetcher);
	return json?.data as T;
}

/** GET an endpoint and return the untouched payload (no envelope unwrapping). */
export async function apiGetRaw<T>(path: string, query: Query = {}, fetcher?: Fetcher): Promise<T> {
	return request<T>(path, query, fetcher);
}

/** GET an endpoint and keep the whole payload (some endpoints add `meta`). */
export async function apiGetWithMeta<T>(
	path: string,
	query: Query = {},
	fetcher?: Fetcher
): Promise<{ data: T; meta?: Record<string, unknown> }> {
	const json = await request<{ data?: T; meta?: Record<string, unknown> }>(path, query, fetcher);
	return { data: json?.data as T, meta: json?.meta };
}

/** POST JSON to an endpoint and unwrap the envelope. Throws ApiError with the API message. */
export async function apiPost<T = unknown>(path: string, body: Record<string, unknown> = {}): Promise<T> {
	const response = await fetch(`${API_BASE}${path}`, {
		method: 'POST',
		headers: { ...authHeaders(), 'Content-Type': 'application/json' },
		body: JSON.stringify(body)
	});

	const json = await response.json().catch(() => null);

	if (!response.ok) {
		throw new ApiError(json?.message ?? `Request failed (${response.status})`, response.status);
	}

	return (json?.data ?? json) as T;
}

async function send(method: string, path: string, body?: Record<string, unknown>): Promise<unknown> {
	const response = await fetch(`${API_BASE}${path}`, {
		method,
		headers: { ...authHeaders(), ...(body ? { 'Content-Type': 'application/json' } : {}) },
		body: body ? JSON.stringify(body) : undefined
	});

	const json = await response.json().catch(() => null);

	if (!response.ok) {
		throw new ApiError(json?.message ?? `Request failed (${response.status})`, response.status);
	}

	return json?.data ?? json;
}

export const apiPut = <T = unknown>(path: string, body: Record<string, unknown> = {}) =>
	send('PUT', path, body) as Promise<T>;

export const apiDelete = <T = unknown>(path: string) => send('DELETE', path) as Promise<T>;

/** POST FormData for multipart file uploads. */
export async function apiPostForm<T = unknown>(path: string, formData: FormData): Promise<T> {
	const response = await fetch(`${API_BASE}${path}`, {
		method: 'POST',
		headers: authHeaders(),
		body: formData
	});

	const json = await response.json().catch(() => null);

	if (!response.ok) {
		throw new ApiError(json?.message ?? `Request failed (${response.status})`, response.status);
	}

	return (json?.data ?? json) as T;
}
