/**
 * Minimal, dependency-free HTML sanitizer for provider content (e.g. CJ
 * Dropshipping product descriptions). It removes the dangerous elements,
 * inline event handlers and javascript:-URLs while keeping the formatting
 * (paragraphs, lists, images, links) that the description uses.
 *
 * Not a full HTML parser — for untrusted rich content prefer a dedicated
 * sanitizer; this is a pragmatic guard for display-only supplier HTML.
 */
const BLOCK_ELEMENT = /<\s*(script|style|iframe|object|embed|link|meta|form|input|button|textarea|select)\b[\s\S]*?<\s*\/\s*\1\s*>/gi;
const BLOCK_VOID_ELEMENT = /<\s*(script|style|iframe|object|embed|link|meta|input|button|base)\b[^>]*\/?>/gi;
const EVENT_ATTR = /\son\w+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi;
const DANGEROUS_URL = /\s(href|src|xlink:href)\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi;

export function sanitizeHtml(input: string | null | undefined): string {
	if (!input) return '';

	let html = String(input);

	html = html.replace(BLOCK_ELEMENT, '');
	html = html.replace(BLOCK_VOID_ELEMENT, '');
	html = html.replace(EVENT_ATTR, '');
	html = html.replace(DANGEROUS_URL, (match, attr: string, value: string) => {
		const raw = value.replace(/^["']|["']$/g, '').trim().toLowerCase();
		if (raw.startsWith('javascript:') || raw.startsWith('data:text/html')) {
			return '';
		}
		return match;
	});

	return html;
}
