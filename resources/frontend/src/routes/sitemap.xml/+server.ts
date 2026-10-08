import { apiGet } from '$lib/utils/api';
import type { ApiProduct } from '$lib/services/products';
import type { ApiCategory } from '$lib/services/categories';

export const prerender = false;

const STATIC = ['/', '/shop', '/contact', '/about', '/faq', '/blog'];

export async function GET({ url, fetch }) {
	const origin = url.origin;
	const locs: string[] = STATIC.map((p) => `${origin}${p}`);

	try {
		const cats = await apiGet<ApiCategory[]>('/category', { all: 1 }, fetch);
		for (const c of Array.isArray(cats) ? cats : []) {
			locs.push(`${origin}/shop?category=${c.id}`);
		}
	} catch {
		/* ignore */
	}

	try {
		for (let page = 1; page <= 40; page++) {
			const products = await apiGet<ApiProduct[]>('/product', { per_page: 50, page }, fetch);
			const rows = Array.isArray(products) ? products : [];
			if (rows.length === 0) break;
			for (const p of rows) {
				if (p.slug) locs.push(`${origin}/elan/${p.slug}`);
			}
			if (rows.length < 50) break;
		}
	} catch {
		/* ignore */
	}

	const body =
		'<?xml version="1.0" encoding="UTF-8"?>\n' +
		'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
		locs.map((loc) => `  <url><loc>${loc.replace(/&/g, '&amp;')}</loc></url>`).join('\n') +
		'\n</urlset>';

	return new Response(body, { headers: { 'Content-Type': 'application/xml; charset=utf-8' } });
}
