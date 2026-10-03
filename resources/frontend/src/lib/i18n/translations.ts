import az from './locales/az';
import en from './locales/en';
import ru from './locales/ru';
import tr from './locales/tr';
import { derived } from 'svelte/store';
import { features } from '$lib/services/features';

export type Locale = 'az' | 'en' | 'ru' | 'tr';

export const defaultLocale: Locale = 'az';

export const languages: { value: Locale; label: string }[] = [
	{ value: 'az', label: 'Azərbaycanca' },
	{ value: 'en', label: 'English' },
	{ value: 'ru', label: 'Русский' },
	{ value: 'tr', label: 'Türkçe' }
];

/**
 * Languages the storefront may switch between. Without the `multi_language`
 * entitlement only the default locale is offered; individual locales can then
 * be turned off with `lang_xx` flags.
 */
export const languageOptions = derived(features, ($features) => {
	const flags = $features as Record<string, unknown>;
	const multiLanguage = flags.multi_language !== false;

	return languages
		.filter((language) =>
			multiLanguage ? flags[`lang_${language.value}`] !== false : language.value === defaultLocale
		)
		.map((language) => ({ ...language, status: true }));
});

export const messages: Record<Locale, Record<string, string>> = {
	en,
	az,
	ru,
	tr
};
