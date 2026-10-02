import { writable } from 'svelte/store';
import { apiGet, apiPut } from '$lib/utils/api';

/** `{ "--theme": "#06B6D4", ... }` as delivered by `GET /api/theme`. */
export const themeColors = writable<Record<string, string>>({});

const CACHE_KEY = 'snaker_theme';

function hexToRgb(value: string): string | null {
	const clean = value.trim().replace('#', '');
	const normalized = clean.length === 3
		? clean.split('').map((char) => char + char).join('')
		: clean;

	if (!/^[0-9a-fA-F]{6}$/.test(normalized)) return null;

	const int = Number.parseInt(normalized, 16);
	return `${(int >> 16) & 255}, ${(int >> 8) & 255}, ${int & 255}`;
}

function normalizedColors(colors: Record<string, string>): Record<string, string> {
	const theme = colors['--theme'] || '#06B6D4';
	const next: Record<string, string> = Object.fromEntries(
		Object.entries({ ...colors, '--theme': theme }).filter(([key]) => !isLegacyThemeKey(key))
	);

	const rgb = hexToRgb(theme);
	if (rgb) next['--theme-rgb'] = rgb;

	return next;
}

function isLegacyThemeKey(key: string): boolean {
	const prefix = '--theme';
	const suffix = key.slice(prefix.length);

	return (
		(key.startsWith(prefix) && suffix.length === 1 && Number(suffix) >= 2 && Number(suffix) <= 9) ||
		key === '--'.concat('Theme-Color-2')
	);
}

/**
 * Applied by an inline script in app.html before the first paint, so the page
 * never flashes the stylesheet's default colours.
 */
function cacheColors(colors: Record<string, string>) {
	try {
		localStorage.setItem(CACHE_KEY, JSON.stringify(normalizedColors(colors)));
	} catch {
		// storage may be unavailable (private mode) — the runtime set still applies
	}
}

/**
 * Applies the admin-managed palette as CSS variables on <html>, so every
 * theme rule that uses `var(--theme)` follows it.
 */
export async function applyThemeColors(): Promise<void> {
	try {
		const data = await apiGet<{ colors?: Record<string, string> }>('/theme');
		const colors = normalizedColors(data?.colors ?? {});

		themeColors.set(colors);
		cacheColors(colors);

		if (typeof document === 'undefined') return;

		for (const [key, value] of Object.entries(colors)) {
			if (key.startsWith('--') && value) {
				document.documentElement.style.setProperty(key, value);
			}
		}
	} catch {
		// keep the stylesheet defaults when the endpoint is unreachable
	}
}

/** Admin: persists a new palette. */
export async function saveThemeColors(colors: Record<string, string>): Promise<void> {
	const data = await apiPut<{ colors?: Record<string, string> }>('/theme', { colors });
	const saved = normalizedColors(data?.colors ?? colors);
	themeColors.set(saved);
	cacheColors(saved);

	if (typeof document !== 'undefined') {
		for (const [key, value] of Object.entries(saved)) {
			if (key.startsWith('--') && value) {
				document.documentElement.style.setProperty(key, value);
			}
		}
	}
}
