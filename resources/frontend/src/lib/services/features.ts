import { writable } from 'svelte/store';
import { apiGet } from '$lib/utils/api';

type Fetcher = typeof fetch;

/**
 * Feature flags as delivered by the API (`GET /features`). Keys are dynamic:
 * whatever the Manager catalogue exposes, no hardcoded list here.
 */
export type ApiFeatures = Record<string, boolean | undefined>;

/** Shared store; loaded once in +layout.svelte and read reactively everywhere. */
export const features = writable<ApiFeatures>({});

export async function loadFeatures(fetcher?: Fetcher): Promise<ApiFeatures> {
	try {
		const flags = await apiGet<ApiFeatures>('/features', {}, fetcher);
		const value = flags && typeof flags === 'object' ? flags : {};
		features.set(value);
		return value;
	} catch {
		features.set({});
		return {};
	}
}

/** Backwards-compatible one-shot fetch. */
export async function fetchFeatures(fetcher?: Fetcher): Promise<ApiFeatures> {
	return loadFeatures(fetcher);
}
