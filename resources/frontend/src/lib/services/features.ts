import { writable } from 'svelte/store';

type Fetcher = typeof fetch;

/** Feature flags. There is no gating any more, so every key reads as enabled. */
export type ApiFeatures = Record<string, boolean | undefined>;

/**
 * Any key access resolves to `true`, so both `$features.someFlag` and
 * `featureEnabled('some_flag')` behave as "always on".
 */
const ALWAYS_ON = new Proxy({} as ApiFeatures, {
	get: () => true
});

/** Shared store; kept for components that read `$features` reactively. */
export const features = writable<ApiFeatures>(ALWAYS_ON);

export async function loadFeatures(fetcher?: Fetcher): Promise<ApiFeatures> {
	features.set(ALWAYS_ON);
	return ALWAYS_ON;
}

/** Backwards-compatible one-shot fetch. */
export async function fetchFeatures(fetcher?: Fetcher): Promise<ApiFeatures> {
	return loadFeatures(fetcher);
}

/** Every feature is on. */
export function featureEnabled(_key: string): boolean {
	return true;
}
