import type { Handle } from '@sveltejs/kit';

/**
 * Multi-tenant SSR fix + edge caching.
 *
 * During SSR the API is reached through INTERNAL_API_URL (http://snaker-web/api),
 * so Laravel would see the internal container host and could not resolve the
 * tenant. Every server-side API call therefore carries the original public host
 * and scheme in X-Forwarded-* headers; Laravel (behind a trusted proxy) picks
 * the tenant database from that host.
 *
 * Public pages also get CDN cache headers so Cloudflare can serve them from the
 * edge instead of a slow round-trip to the origin. User-specific pages
 * (cart, checkout, dashboard, settings, wishlist, login/register) are NOT cached.
 */
const PUBLIC_PREFIXES = [
	'/',
	'/shop',
	'/about',
	'/faq',
	'/terms',
	'/privacy',
	'/blog',
	'/contact',
	'/categories',
	'/look-book'
];

function isCacheable(pathname: string): boolean {
	return PUBLIC_PREFIXES.some((p) => pathname === p || pathname.startsWith(p + '/'));
}

export const handle: Handle = async ({ event, resolve }) => {
	const originalFetch = event.fetch;

	event.fetch = (input, init = {}) => {
		const headers = new Headers(init.headers);
		headers.set('X-Forwarded-Host', event.url.host);
		headers.set('X-Forwarded-Proto', event.url.protocol.replace(':', ''));

		return originalFetch(input, { ...init, headers });
	};

	const response = await resolve(event);

	if (event.request.method === 'GET') {
		if (isCacheable(event.url.pathname) && !event.url.searchParams.has('_data')) {
			// Browser: revalidate; CDN (Cloudflare s-maxage): serve from edge.
			response.headers.set(
				'Cache-Control',
				'public, max-age=0, s-maxage=300, stale-while-revalidate=600'
			);
		} else {
			// User-specific pages (cart, checkout, dashboard, settings, wishlist,
			// login/register) must never be cached by a CDN "cache everything" rule.
			response.headers.set('Cache-Control', 'private, no-store');
		}
	}

	return response;
};
