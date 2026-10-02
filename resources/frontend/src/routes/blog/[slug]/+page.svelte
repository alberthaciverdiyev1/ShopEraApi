<script lang="ts">
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { fetchFeatures } from '$lib/services/features';

	onMount(async () => {
		const f = await fetchFeatures();
		if (f.blog === false) goto('/');
	});
	import { page } from '$app/state';
	import Breadcrumb from '$lib/components/layout/Breadcrumb.svelte';
	import BlogCard from '$lib/components/cards/BlogCard.svelte';
	import {
		fetchBlogBySlug,
		blogImage,
		blogUrl,
		formatBlogDate,
		type ApiBlog,
		type BlogCategory,
		fetchBlogCategories
	} from '$lib/services/blog';
	import { translate } from '$lib/i18n';

	let loading = $state(true);
	let blog = $state<ApiBlog | null>(null);
	let categories = $state<BlogCategory[]>([]);
	let notFound = $state(false);

	const slug = $derived(page.params.slug);

	async function loadArticle(articleSlug: string) {
		if (!articleSlug) return;
		loading = true;
		notFound = false;
		try {
			const [blogData, cats] = await Promise.all([
				fetchBlogBySlug(articleSlug),
				fetchBlogCategories()
			]);
			blog = blogData;
			categories = cats;
		} catch (err) {
			console.error('Failed to load blog:', err);
			notFound = true;
			blog = null;
		} finally {
			loading = false;
		}
	}

	$effect(() => {
		if (slug) {
			loadArticle(slug);
		}
	});
</script>

<svelte:head>
	<title>{blog?.title ? `${blog.title} — ShopEra` : 'Blog Details — ShopEra'}</title>
</svelte:head>

<Breadcrumb title={blog?.title || 'Blog Details'} />

<section class="blog-details-section section-padding fix">
	<div class="container">
		{#if loading}
			<div class="row gx-40">
				<div class="col-lg-8">
					<div class="detail-skeleton">
						<div class="skeleton-title mb-3"></div>
						<div class="skeleton-meta mb-4"></div>
						<div class="skeleton-banner mb-4"></div>
						<div class="skeleton-text mb-2"></div>
						<div class="skeleton-text mb-2"></div>
						<div class="skeleton-text mb-2 w-75"></div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="detail-skeleton p-4">
						<div class="skeleton-title mb-3 w-50"></div>
						<div class="skeleton-text mb-2"></div>
						<div class="skeleton-text mb-2"></div>
					</div>
				</div>
			</div>
		{:else if notFound || !blog}
			<div class="not-found-box text-center py-5">
				<i class="fa-solid fa-circle-exclamation fa-3x text-danger mb-3"></i>
				<h3>{$translate('Article Not Found')}</h3>
				<p class="text-muted">
					{$translate('The blog post you are looking for does not exist or has been removed.')}
				</p>
				<a href="/blog" class="theme-btn-2 mt-3">
					<i class="fa-solid fa-arrow-left me-2"></i>
					{$translate('Back to Blogs')}
				</a>
			</div>
		{:else}
			<div class="row gx-40 gy-4">
				<!-- Main Content -->
				<div class="col-lg-8">
					<article class="blog-post-details">
						<div class="post-header mb-4">
							<div class="post-meta-top d-flex align-items-center gap-3 mb-2 flex-wrap">
								{#if blog.category}
									<span class="category-badge">{blog.category}</span>
								{/if}
								{#if blog.published_at || blog.created_at}
									<span class="post-date text-muted">
										<i class="fa-regular fa-calendar me-1"></i>
										{formatBlogDate(blog.published_at || blog.created_at)}
									</span>
								{/if}
								{#if blog.author_name}
									<span class="post-author text-muted">
										<i class="fa-regular fa-user me-1"></i>
										{blog.author_name}
									</span>
								{/if}
								{#if blog.views !== undefined}
									<span class="post-views text-muted">
										<i class="fa-regular fa-eye me-1"></i>
										{blog.views} {$translate('views')}
									</span>
								{/if}
							</div>
							<h1 class="post-title">{blog.title}</h1>
						</div>

						<div class="post-featured-image mb-4">
							<img src={blogImage(blog)} alt={blog.title} />
						</div>

						{#if blog.description}
							<div class="post-lead mb-4">
								<p>{blog.description}</p>
							</div>
						{/if}

						<div class="post-body mb-5">
							{#if blog.content}
								{@html blog.content}
							{/if}
						</div>

						{#if blog.tags && blog.tags.length > 0}
							<div class="post-tags-box d-flex align-items-center gap-2 flex-wrap pt-3 border-top">
								<span class="tags-label fw-bold">{$translate('Tags')}:</span>
								{#each blog.tags as tag (tag)}
									<a href="/blog" class="tag-pill">#{tag}</a>
								{/each}
							</div>
						{/if}
					</article>

					<!-- Related Posts -->
					{#if blog.related_posts && blog.related_posts.length > 0}
						<div class="related-posts mt-5 pt-4 border-top">
							<h3 class="mb-4">{$translate('Related Articles')}</h3>
							<div class="row g-3">
								{#each blog.related_posts as related (related.id)}
									<div class="col-md-6 col-6">
										<BlogCard blog={related} />
									</div>
								{/each}
							</div>
						</div>
					{/if}
				</div>

				<!-- Sidebar -->
				<div class="col-lg-4">
					<aside class="main-sidebar-2">
						<!-- Categories Widget -->
						{#if categories.length > 0}
							<div class="single-sidebar-widget mb-4">
								<div class="single-sidebar-widget__wid-title mb-3">
									<h4 class="fw-bold">{$translate('Categories')}</h4>
								</div>
								<ul class="category-list list-unstyled m-0">
									{#each categories as cat (cat.name)}
										<li class="d-flex justify-content-between align-items-center py-2 border-bottom">
											<a href="/blog" class="text-dark text-decoration-none hover-theme">
												{cat.name}
											</a>
											<span class="badge bg-light text-dark rounded-pill">
												{cat.count}
											</span>
										</li>
									{/each}
								</ul>
							</div>
						{/if}

						<!-- Recent Posts Widget -->
						{#if blog.related_posts && blog.related_posts.length > 0}
							<div class="single-sidebar-widget mb-4">
								<div class="single-sidebar-widget__wid-title mb-3">
									<h4 class="fw-bold">{$translate('Recent Posts')}</h4>
								</div>
								<div class="recent-list d-flex flex-column gap-3">
									{#each blog.related_posts as recent (recent.id)}
										<div class="recent-item d-flex gap-3 align-items-center">
											<div class="recent-thumb rounded overflow-hidden">
												<a href={blogUrl(recent)}>
													<img src={blogImage(recent)} alt={recent.title} />
												</a>
											</div>
											<div class="recent-content">
												<span class="text-muted small d-block mb-1">
													<i class="fa-regular fa-calendar me-1"></i>
													{formatBlogDate(recent.published_at || recent.created_at)}
												</span>
												<h6 class="m-0 line-clamp-2">
													<a href={blogUrl(recent)} class="text-dark text-decoration-none">
														{recent.title}
													</a>
												</h6>
											</div>
										</div>
									{/each}
								</div>
							</div>
						{/if}
					</aside>
				</div>
			</div>
		{/if}
	</div>
</section>

<style>
	.blog-details-section {
		padding-top: 50px;
		padding-bottom: 80px;
		background: #ffffff;
	}

	.category-badge {
		background: #ff4035;
		color: #ffffff;
		padding: 4px 12px;
		border-radius: 999px;
		font-size: 12px;
		font-weight: 600;
	}

	.post-title {
		font-size: 32px;
		font-weight: 800;
		line-height: 1.3;
		color: #111827;
		margin-top: 10px;
	}

	.post-featured-image {
		width: 100%;
		border-radius: 16px;
		overflow: hidden;
		aspect-ratio: 16 / 9;
		background: #f3f4f6;
	}

	.post-featured-image img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.post-lead {
		font-size: 18px;
		line-height: 1.7;
		color: #374151;
		font-weight: 500;
		border-left: 4px solid #ff4035;
		padding-left: 18px;
		font-style: italic;
	}

	.post-body {
		font-size: 16px;
		line-height: 1.8;
		color: #4b5563;
	}

	.post-body :global(h2),
	.post-body :global(h3),
	.post-body :global(h4) {
		color: #111827;
		font-weight: 700;
		margin-top: 24px;
		margin-bottom: 12px;
	}

	.post-body :global(p) {
		margin-bottom: 16px;
	}

	.tag-pill {
		display: inline-block;
		padding: 4px 12px;
		border-radius: 6px;
		background: #f3f4f6;
		color: #4b5563;
		font-size: 13px;
		text-decoration: none;
		transition: all 0.2s;
	}

	.tag-pill:hover {
		background: #ff4035;
		color: #ffffff;
	}

	.hover-theme:hover {
		color: #ff4035 !important;
	}

	.recent-thumb {
		width: 72px;
		height: 72px;
		flex-shrink: 0;
	}

	.recent-thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.line-clamp-2 {
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		font-size: 14px;
		line-height: 1.4;
	}

	.line-clamp-2 a:hover {
		color: #ff4035 !important;
	}

	/* Skeletons */
	.skeleton-title {
		height: 36px;
		background: #eee;
		border-radius: 8px;
	}

	.skeleton-meta {
		height: 20px;
		width: 50%;
		background: #f0f0f0;
		border-radius: 4px;
	}

	.skeleton-banner {
		height: 320px;
		background: #eee;
		border-radius: 12px;
	}

	.skeleton-text {
		height: 16px;
		background: #f4f4f4;
		border-radius: 4px;
	}

	@media (max-width: 767px) {
		.blog-details-section {
			padding-top: 25px;
			padding-bottom: 50px;
		}

		.post-title {
			font-size: 22px;
		}

		.post-lead {
			font-size: 15px;
		}
	}
</style>
