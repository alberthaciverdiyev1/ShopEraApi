import { apiGet } from '$lib/utils/api';

export type FilterType = 'select' | 'radio' | 'checkbox' | 'input' | 'number' | 'range' | string;

export interface FilterDefinition {
	id: number;
	title: string | Record<string, string>;
	type: FilterType;
	/** Values configured on the filter itself. */
	options?: string[];
	/** Values that actually occur on products (falls back to `options`). */
	values?: string[];
}

export function filterTitle(filter: FilterDefinition, locale = 'az'): string {
	const { title } = filter;
	if (typeof title === 'string') return title;
	return title?.[locale] ?? Object.values(title ?? {})[0] ?? '';
}

/** Values a control should offer: configured options first, then real product values. */
export function filterChoices(filter: FilterDefinition): string[] {
	const choices = filter.options?.length ? filter.options : (filter.values ?? []);
	return [...new Set(choices.filter(Boolean))];
}

/** Filters that exist for the given category (needs one — there is no global list). */
export async function fetchCategoryFilters(categoryId: number | null): Promise<FilterDefinition[]> {
	if (!categoryId) return [];
	const filters = await apiGet<FilterDefinition[]>('/category-filters', { category_id: categoryId });
	return Array.isArray(filters) ? filters : [];
}
