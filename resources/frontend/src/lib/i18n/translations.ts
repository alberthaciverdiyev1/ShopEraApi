import az from './locales/az';
import en from './locales/en';
import ru from './locales/ru';
import tr from './locales/tr';
import {features} from '$lib/services/features';


export type Locale = 'az' | 'en' | 'ru' | 'tr';

export const defaultLocale: Locale = 'az';

export const languages: { value: Locale; label: string, status: boolean }[] = [
    {value: 'az', label: 'Azərbaycanca', active: $features.lang_az ?? true},
    {value: 'en', label: 'English', active: $features.lang_en ?? true},
    {value: 'ru', label: 'Русский', active: $features.lang_en ?? true},
    {value: 'tr', label: 'Türkçe', active: $features.lang_en ?? true}
];

export const messages: Record<Locale, Record<string, string>> = {
    en,
    az,
    ru,
    tr
};
