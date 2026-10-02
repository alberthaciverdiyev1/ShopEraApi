import { writable } from 'svelte/store';
import { apiGet } from '$lib/utils/api';

export interface ApiStoryProduct {
	id: number;
	title: string;
	image?: string | null;
	price?: number | null;
	discount?: number | null;
	url?: string | null;
}

export interface ApiStoryVideo {
	id: number;
	image?: string | null;
	video?: string | null;
	product_id?: number | null;
	created_at?: string;
	expires_at?: string | null;
	is_story_hidden?: boolean;
	is_story_active?: boolean;
	product?: ApiStoryProduct | null;
}

export const storyVideos = writable<ApiStoryVideo[]>([]);
export const viewedStoryIds = writable<Set<number>>(new Set());

const VIEWED_STORAGE_KEY = 'snaker_viewed_stories';

export function initViewedStories(): void {
	if (typeof window === 'undefined') return;
	try {
		const raw = localStorage.getItem(VIEWED_STORAGE_KEY);
		if (raw) {
			const arr = JSON.parse(raw);
			if (Array.isArray(arr)) {
				viewedStoryIds.set(new Set(arr));
			}
		}
	} catch {
		// Ignore storage errors
	}
}

export function markStoryAsViewed(storyId: number): void {
	viewedStoryIds.update((set) => {
		const next = new Set(set);
		next.add(storyId);
		if (typeof window !== 'undefined') {
			try {
				localStorage.setItem(VIEWED_STORAGE_KEY, JSON.stringify([...next]));
			} catch {
				// Ignore storage errors
			}
		}
		return next;
	});
}

export async function fetchStoryVideos(): Promise<ApiStoryVideo[]> {
	try {
		const data = await apiGet<ApiStoryVideo[]>('/story');
		const list = Array.isArray(data) ? data.filter((s) => (s.image || s.video) && s.is_story_active !== false) : [];
		storyVideos.set(list);
		return list;
	} catch (e) {
		console.error('Failed to load story videos', e);
		storyVideos.set([]);
		return [];
	}
}

export function storyProductUrl(story: ApiStoryVideo): string {
	if (story.product?.id) {
		return `/shop/details?id=${story.product.id}`;
	}
	return '/shop';
}
