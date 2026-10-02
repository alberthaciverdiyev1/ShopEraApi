<script lang="ts">
	import { onMount } from 'svelte';
	import BlogCard from '$lib/components/cards/BlogCard.svelte';
	import { fetchRecentBlogs, type ApiBlog } from '$lib/services/blog';
	import { translate } from '$lib/i18n';

	let { blogs: serverBlogs = [] }: { blogs?: ApiBlog[] } = $props();
	let clientBlogs = $state<ApiBlog[] | null>(null);
	let loading = $state(false);
	const blogs = $derived(clientBlogs ?? serverBlogs);

	onMount(async () => {
		loading = blogs.length === 0;
		if (!loading) return;
		try {
			clientBlogs = await fetchRecentBlogs(4);
		} catch (err) {
			console.error('Failed to load recent blogs:', err);
		} finally {
			loading = false;
		}
	});
</script>

{#if !loading && blogs.length > 0}
	<!-- Blog Section -->
	<section class="blog-section section-padding pt-0 fix">
		<div class="container">
			<div class="blog-wrapper style1">
				<div class="section-title text-center">
					<h2 class="title">{$translate('Latest news & blog')}</h2>
				</div>
				<div class="row">
					{#each blogs as blog (blog.id)}
						<div class="col-xl-3 col-md-6 col-6">
							<BlogCard {blog} />
						</div>
					{/each}
				</div>
			</div>
		</div>
	</section>
{/if}

<style>
	@media (max-width: 575px) {
		.blog-section :global(.row) {
			--bs-gutter-x: 12px;
			--bs-gutter-y: 14px;
		}

		.blog-section :global(.section-title) {
			margin-bottom: 22px;
		}
	}
</style>
