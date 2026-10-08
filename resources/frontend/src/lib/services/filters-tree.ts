import { apiGet } from '$lib/utils/api';

export interface FilterValueNode {
	id: number;
	title: Record<string, string> | string | null;
	parent_value_id: number | null;
}

export interface ApiFilterNode {
	id: number;
	title: Record<string, string> | string | null;
	type: string;
	required: boolean;
	sort_order: number;
	depends_on_filter_id: number | null;
	values: FilterValueNode[];
}

function label(value: Record<string, string> | string | null, locale = 'az'): string {
	if (!value) return '';
	if (typeof value === 'string') return value;
	return value[locale] ?? value.az ?? value.en ?? Object.values(value)[0] ?? '';
}

/** Dependent filter tree for a subcategory (brand → model → storage …). */
export async function loadFilterTree(categoryId: number): Promise<ApiFilterNode[]> {
	try {
		const tree = await apiGet<ApiFilterNode[]>('/filters/tree', { category_id: categoryId });
		return Array.isArray(tree) ? tree : [];
	} catch {
		return [];
	}
}

export const filterTitle = (filter: ApiFilterNode, locale = 'az') => label(filter.title, locale);
export const filterValueTitle = (value: FilterValueNode, locale = 'az') => label(value.title, locale);
