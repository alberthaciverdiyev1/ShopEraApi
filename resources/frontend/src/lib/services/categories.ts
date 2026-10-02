import { writable } from 'svelte/store';
import { cachedGet } from '$lib/utils/api-cache';

export interface ApiCategory {
	id: number;
	name: string | Record<string, string>;
	parent_id: number | null;
	image?: string | null;
	is_active?: boolean;
	sort_order?: number;
	products_count?: number;
	children?: ApiCategory[];
}

export interface Category extends ApiCategory {
	children: Category[];
}

export const categories = writable<Category[]>([]);
export const categoriesLoading = writable(false);
export const categoriesError = writable<string | null>(null);

/** Localised name: the API may return a plain string or a { az, en, ru, tr } map. */
export function categoryName(category: ApiCategory, locale = 'az'): string {
	const { name } = category;
	if (typeof name === 'string') return name;
	return name?.[locale] ?? Object.values(name ?? {})[0] ?? '';
}

function buildTree(flat: ApiCategory[]): Category[] {
	const byParent = new Map<number | null, Category[]>();
	const nodes = new Map<number, Category>();

	for (const item of flat) {
		nodes.set(item.id, { ...item, children: [] });
	}

	for (const node of nodes.values()) {
		const key = node.parent_id ?? null;
		if (!byParent.has(key)) byParent.set(key, []);
		byParent.get(key)!.push(node);
	}

	for (const node of nodes.values()) {
		node.children = byParent.get(node.id) ?? [];
	}

	return byParent.get(null) ?? [];
}

let requested = false;

/** Fetch all categories once and nest them by parent_id. */
export async function loadCategories(force = false): Promise<void> {
	if (requested && !force) return;
	requested = true;

	categoriesLoading.set(true);
	categoriesError.set(null);

	try {
		// `all=1` returns every category flat (parents aren't returned with children loaded).
		const flat = await cachedGet<ApiCategory[]>('/category', { all: 1 });
		categories.set(buildTree(flat));
	} catch (error) {
		categoriesError.set(error instanceof Error ? error.message : 'Failed to load categories');
	} finally {
		categoriesLoading.set(false);
	}
}
