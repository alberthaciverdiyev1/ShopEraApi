<script lang="ts">
	import { blogImage, blogUrl, formatBlogDate, type ApiBlog } from '$lib/services/blog';
	import { translate } from '$lib/i18n';

	let { blog }: { blog: ApiBlog } = $props();

	const url = $derived(blogUrl(blog));
	const image = $derived(blogImage(blog));
	const date = $derived(formatBlogDate(blog.published_at || blog.created_at));
</script>

<div class="blog_card_two">
	<div class="blog_card_two__thumb">
		<a href={url}>
			<img src={image} alt={blog.title} loading="lazy" />
		</a>
	</div>
	<div class="blog_card_two__content">
		<ul class="blog_card_two__content--post-list d-flex align-items-center">
			<li>
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path
						d="M14.5441 5.19275C14.5441 7.69093 12.4995 9.7355 10.0013 9.7355C7.50317 9.7355 5.45859 7.69093 5.45859 5.19275C5.45859 2.69457 7.50313 0.65 10.0013 0.65C12.4995 0.65 14.5441 2.69458 14.5441 5.19275Z"
						stroke="#FF4035"
						stroke-width="1.3"
					/>
					<path
						d="M18.265 14.6706C18.1058 14.9458 17.9247 15.2073 17.7176 15.4766L17.7174 15.4765L17.7095 15.4873C17.421 15.8788 17.0852 16.2373 16.7301 16.5924C16.4332 16.8892 16.0939 17.186 15.7574 17.4385C14.0801 18.6911 12.0628 19.3499 9.97879 19.3499C7.89901 19.3499 5.88571 18.6938 4.21041 17.4461C3.84652 17.1504 3.51432 16.8792 3.22751 16.5924L3.22054 16.5854L3.21337 16.5787C2.85728 16.2436 2.54303 15.8877 2.2481 15.4874L2.24812 15.4873L2.24478 15.4829C2.06257 15.24 1.87385 14.9756 1.71984 14.7169C1.83683 14.4559 1.98519 14.1847 2.14586 13.9526L2.14597 13.9527L2.15349 13.9413C3.07049 12.5556 4.5377 11.6388 6.16707 11.4148L6.18665 11.4121L6.20603 11.4082C6.23151 11.4031 6.29559 11.4117 6.34612 11.4496L6.34611 11.4496L6.35012 11.4525C7.41715 12.2401 8.68694 12.6453 10.0014 12.6453C11.3159 12.6453 12.5857 12.2401 13.6528 11.4525L13.6528 11.4525L13.6568 11.4496C13.6722 11.438 13.741 11.408 13.8498 11.4167C15.4695 11.6435 16.9128 12.5568 17.8531 13.9468L17.853 13.9469L17.857 13.9526C18.0172 14.1839 18.1563 14.4231 18.265 14.6706Z"
						stroke="#FF4035"
						stroke-width="1.3"
					/>
				</svg>
				{blog.author_name ? `${blog.author_name}` : 'ShopEra'}
			</li>
			{#if blog.category}
				<li>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path
							d="M8.05666 18.75H8.05504C7.46832 18.7495 6.91657 18.5207 6.50187 18.1052L0.660341 12.2553C-0.194072 11.3994 -0.194072 10.0065 0.660341 9.15058L8.53478 1.26102C9.3463 0.44792 10.426 0 11.575 0H16.5709C17.7824 0 18.7682 0.985546 18.7682 2.19726V7.17785C18.7682 8.32602 18.3208 9.40532 17.5084 10.2167L9.60951 18.1074C9.19455 18.5218 8.64306 18.75 8.05666 18.75ZM11.575 1.46484C10.8179 1.46484 10.1064 1.75998 9.57163 2.29579L1.69707 10.1853C1.41222 10.4708 1.41222 10.9349 1.69707 11.2203L7.53857 17.0702C7.6767 17.2086 7.86051 17.285 8.05619 17.2851H8.05677C8.1529 17.2854 8.24812 17.2666 8.33694 17.2299C8.42577 17.1931 8.50643 17.1391 8.57427 17.071L16.4732 9.18046C17.0086 8.6458 17.3034 7.93447 17.3034 7.17788V2.19726C17.3034 1.79341 16.9748 1.46484 16.5709 1.46484H11.575ZM13.458 7.43408C12.2465 7.43408 11.2608 6.44853 11.2608 5.23681C11.2608 4.0251 12.2465 3.03955 13.458 3.03955C14.6696 3.03955 15.6553 4.0251 15.6553 5.23681C15.6553 6.44853 14.6696 7.43408 13.458 7.43408ZM13.458 4.50439C13.0542 4.50439 12.7256 4.83296 12.7256 5.23681C12.7256 5.64067 13.0542 5.96924 13.458 5.96924C13.862 5.96924 14.1905 5.64067 14.1905 5.23681C14.1905 4.83296 13.862 4.50439 13.458 4.50439Z"
							fill="#FF4035"
						/>
					</svg>
					{blog.category}
				</li>
			{/if}
		</ul>
		<!--{#if date}-->
		<!--	<div class="blog_card_two__content&#45;&#45;date">-->
		<!--		<ul>-->
		<!--			<li>-->
		<!--				{date}-->
		<!--			</li>-->
		<!--		</ul>-->
		<!--	</div>-->
		<!--{/if}-->
		<h3>
			<a href={url} title={blog.title}>{blog.title}</a>
		</h3>
		{#if blog.description}
			<p class="blog-desc">{blog.description}</p>
		{/if}
		<a href={url} class="theme-btn-2 mt-3">
			{$translate('Read More')}
			<i class="fa-solid fa-arrow-right-long ms-2"></i>
		</a>
	</div>
</div>

<style>
	.blog_card_two {
		display: flex;
		flex-direction: column;
		height: 100%;
		border-radius: 14px;
		background: #ffffff;
		overflow: hidden;
		transition: all 0.3s ease;
		box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
	}

	.blog_card_two:hover {
		transform: translateY(-4px);
		box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
	}

	.blog_card_two__thumb {
		position: relative;
		width: 100%;
		aspect-ratio: 16 / 10;
		overflow: hidden;
	}

	.blog_card_two__thumb a {
		display: block;
		width: 100%;
		height: 100%;
	}

	.blog_card_two__thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: transform 0.5s ease;
	}

	.blog_card_two:hover .blog_card_two__thumb img {
		transform: scale(1.05);
	}

	.blog_card_two__content {
		display: flex;
		flex-direction: column;
		flex-grow: 1;
		padding: 20px 20px 24px;
	}

	.blog_card_two__content h3 {
		font-size: 18px;
		font-weight: 700;
		line-height: 1.4;
		margin-bottom: 10px;
	}

	.blog_card_two__content h3 a {
		color: #111;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		transition: color 0.2s;
	}

	.blog_card_two__content h3 a:hover {
		color: #ff4035;
	}

	.blog-desc {
		color: #666;
		font-size: 14px;
		line-height: 1.6;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		margin-bottom: auto;
	}

	.blog_card_two__content .theme-btn-2 {
		align-self: flex-start;
		margin-top: 16px !important;
	}

	@media (max-width: 575px) {
		.blog_card_two {
			border-radius: 12px;
		}

		.blog_card_two__thumb {
			aspect-ratio: 1 / 0.76;
		}

		.blog_card_two__content {
			padding: 12px 10px 14px;
		}

		.blog_card_two__content--post-list,
		.blog_card_two__content--date {
			display: none !important;
		}

		.blog_card_two__content h3 {
			font-size: 13px;
			line-height: 1.35;
			margin-bottom: 4px;
		}

		.blog-desc {
			display: none !important;
		}

		.blog_card_two__content .theme-btn-2 {
			margin-top: 8px !important;
			font-size: 12px;
			padding: 4px 10px;
		}
	}
</style>
