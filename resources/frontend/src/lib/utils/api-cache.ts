import { apiGet } from '$lib/utils/api';
import { browser } from '$app/environment';

type Fetcher = typeof fetch;

interface Entry {
	data: unknown;
	ts: number;
}

// In-memory only (never localStorage): cleared on page hide/unload anyway.
const cache = new Map<string, Entry>();
let version = '';
let timer: ReturnType<typeof setInterval> | null = null;
let listening = false;

const TTL_MS = 10 * 60 * 1000; // safety net if the version check can't run

function key(path: string, query: Record<string, unknown>): string {
	return path + '?' + JSON.stringify(query);
}

/** Fetches a GET response through the in-memory cache. */
export async function cachedGet<T>(
	path: string,
	query: Record<string, string | number | boolean | Array<string | number>> = {},
	fetcher?: Fetcher
): Promise<T> {
	// Only the browser caches; SSR always fetches so its HTML is never stale.
	if (!browser) {
		return apiGet<T>(path, query, fetcher);
	}

	const k = key(path, query);
	const hit = cache.get(k);

	if (hit && Date.now() - hit.ts < TTL_MS) {
		return hit.data as T;
	}

	const data = await apiGet<T>(path, query, fetcher);
	cache.set(k, { data, ts: Date.now() });

	return data;
}

/** Drops everything — called when the admin changed data. */
export function forgetAll(): void {
	cache.clear();
}

/** Compares the server revision; clears the cache when it changed. */
export async function checkVersion(fetcher?: Fetcher): Promise<void> {
	try {
		const data = await apiGet<{ version?: string }>('/data-version', {}, fetcher);
		const next = data?.version ?? '';

		if (next && next !== version) {
			version = next;
			cache.clear();
		}
	} catch {
		// offline / slow — keep the existing cache
	}
}

/**
 * Boots the revision watcher: one immediate check, then a light poll plus a
 * check whenever the tab becomes visible again. The request is tiny, so this
 * keeps API usage minimal while still reacting to admin changes quickly.
 */
export function startVersionWatch(fetcher?: Fetcher, intervalMs = 15000): void {
	void checkVersion(fetcher);

	if (timer) clearInterval(timer);
	timer = setInterval(() => void checkVersion(fetcher), intervalMs);

	if (typeof document !== 'undefined' && !listening) {
		listening = true;
		document.addEventListener('visibilitychange', () => {
			if (document.visibilityState === 'visible') void checkVersion(fetcher);
		});
	}
}
