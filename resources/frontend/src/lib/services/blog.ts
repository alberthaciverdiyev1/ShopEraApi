import { apiGet, apiGetRaw } from '$lib/utils/api';

type Fetcher = typeof fetch;

export interface ApiBlog {
	id: number;
	title: string;
	slug: string;
	description?: string | null;
	content?: string | null;
	image?: string | null;
	category?: string | null;
	author_name?: string | null;
	author_image?: string | null;
	tags?: string[] | null;
	views?: number;
	published_at?: string | null;
	created_at?: string | null;
	related_posts?: ApiBlog[];
}

export interface BlogMeta {
	current_page: number;
	last_page: number;
	per_page: number;
	total: number;
}

export interface BlogCategory {
	name: string;
	count: number;
}

export interface BlogSidebar {
	categories: BlogCategory[];
	recent_posts: ApiBlog[];
	tags: string[];
}

export interface BlogListResponse {
	items: ApiBlog[];
	meta: BlogMeta;
	sidebar?: BlogSidebar;
}

export interface BlogFilters {
	page?: number;
	per_page?: number;
	category?: string;
	tag?: string;
	search?: string;
}

export function blogUrl(blog: { slug?: string } | null | undefined): string {
	if (!blog?.slug) return '/blog';
	return `/blog/${blog.slug}`;
}

export function blogImage(blog: { image?: string | null } | null | undefined): string {
	if (!blog?.image) return '/assets/images/blog/blogThumb2_1.jpg';
	return blog.image;
}

export function formatBlogDate(dateStr?: string | null): string {
	if (!dateStr) return '';
	try {
		const date = new Date(dateStr);
		return date.toLocaleDateString('en-US', {
			month: 'short',
			day: '2-digit',
			year: 'numeric'
		});
	} catch {
		return dateStr;
	}
}

/**
 * Fetch paginated blog list with optional sidebar data.
 */
export async function fetchBlogs(filters: BlogFilters = {}, fetcher?: Fetcher): Promise<BlogListResponse> {
	const query: Record<string, string | number> = {};
	if (filters.page) query.page = filters.page;
	if (filters.per_page) query.per_page = filters.per_page;
	if (filters.category) query.category = filters.category;
	if (filters.tag) query.tag = filters.tag;
	if (filters.search) query.search = filters.search;

	const res = await apiGet<BlogListResponse>('/blog', query, fetcher);
	return res || { items: [], meta: { current_page: 1, last_page: 1, per_page: 9, total: 0 } };
}

/**
 * Fetch a single blog post details by slug or ID.
 */
export async function fetchBlogBySlug(slug: string, fetcher?: Fetcher): Promise<ApiBlog> {
	return await apiGet<ApiBlog>(`/blog/${encodeURIComponent(slug)}`, {}, fetcher);
}

/**
 * Fetch recent blogs (useful for homepage widgets).
 */
export async function fetchRecentBlogs(limit: number = 4, fetcher?: Fetcher): Promise<ApiBlog[]> {
	const blogs = await apiGet<ApiBlog[]>('/blog/recent', { limit }, fetcher);
	return Array.isArray(blogs) ? blogs : [];
}

/**
 * Fetch blog categories with counts.
 */
export async function fetchBlogCategories(fetcher?: Fetcher): Promise<BlogCategory[]> {
	const categories = await apiGet<BlogCategory[]>('/blog/categories', {}, fetcher);
	return Array.isArray(categories) ? categories : [];
}
