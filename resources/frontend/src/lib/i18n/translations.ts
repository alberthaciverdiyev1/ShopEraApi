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

export const languageOptions = derived(features, ($features) =>
	languages.map((language) => ({
		...language,
		status: ($features as Record<string, unknown>)[`lang_${language.value}`] !== false
	}))
);

export const messages: Record<Locale, Record<string, string>> = {
	en,
	az,
	ru,
	tr
};
