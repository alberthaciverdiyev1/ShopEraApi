import type { Handle } from '@sveltejs/kit';

/**
 * Multi-tenant SSR fix.
 *
 * During SSR the API is reached through INTERNAL_API_URL (http://shopera-web/api),
 * so Laravel would see the internal container host and could not resolve the
 * tenant. Every server-side API call therefore carries the original public host
 * and scheme in X-Forwarded-* headers; Laravel (behind a trusted proxy) picks
 * the tenant database from that host.
 *
 * Browser calls go to same-origin /api via nginx, so they need no help.
 */
export const handle: Handle = async ({ event, resolve }) => {
	const originalFetch = event.fetch;

	event.fetch = (input, init = {}) => {
		const headers = new Headers(init.headers);
		headers.set('X-Forwarded-Host', event.url.host);
		headers.set('X-Forwarded-Proto', event.url.protocol.replace(':', ''));

		return originalFetch(input, { ...init, headers });
	};

	return resolve(event);
};
