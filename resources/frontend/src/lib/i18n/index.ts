import { browser } from '$app/environment';
import { derived, get, writable } from 'svelte/store';
import { defaultLocale, languages, messages, type Locale } from './translations';

const storageKey = 'shopera_locale';
const allowedLocales = new Set<Locale>(languages.map((language) => language.value));

function normalizeLocale(value: string | null | undefined): Locale {
	return allowedLocales.has(value as Locale) ? (value as Locale) : defaultLocale;
}

function format(text: string, values?: Record<string, string | number>): string {
	if (!values) return text;
	return text.replace(/\{(\w+)\}/g, (_, key: string) => String(values[key] ?? `{${key}}`));
}

export const locale = writable<Locale>(defaultLocale);

export const translate = derived(locale, ($locale) => {
	return (key: string, values?: Record<string, string | number>) => {
		const text = messages[$locale]?.[key] ?? messages.en[key] ?? key;
		return format(text, values);
	};
});

export function t(key: string, values?: Record<string, string | number>): string {
	const current = get(locale);
	const text = messages[current]?.[key] ?? messages.en[key] ?? key;
	return format(text, values);
}

export function initLocale() {
	if (!browser) return;
	setLocale(normalizeLocale(window.localStorage.getItem(storageKey)));
}

export function setLocale(value: Locale) {
	const next = normalizeLocale(value);
	locale.set(next);
	if (!browser) return;
	window.localStorage.setItem(storageKey, next);
	document.documentElement.lang = next;
}

export { languages };
export type { Locale };
