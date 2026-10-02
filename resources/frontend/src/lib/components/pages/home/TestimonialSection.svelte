<script lang="ts">
	import { slider } from '$lib/theme/slider';
	import type { ApiFeaturedReview } from '$lib/services/reviews';

	let { featured = [] }: { featured?: ApiFeaturedReview[] } = $props();
</script>

{#if featured && featured.length > 0}
	<!-- Testimonial Section -->
	<section class="testimonial-section fix section-padding margin-bottom-40">
		<div class="container">
			<div class="section-title">
				<h2 class="title">{$translate('What our client say')}</h2>
			</div>
			<div class="swiper testimonial-slider-one">
				<div class="swiper gt-slider" id="featuredReviewSlider" use:slider
					data-slider-options='&#123;"loop": true,"spaceBetween":18,"breakpoints":&#123;"0":&#123;"slidesPerView":1.18,"spaceBetween":14&#125;,"576":&#123;"slidesPerView":1.25,"centeredSlides":true,"spaceBetween":18&#125;,"768":&#123;"slidesPerView":1.35,"spaceBetween":20&#125;,"992":&#123;"slidesPerView":2,"spaceBetween":24&#125;,"1200":&#123;"slidesPerView":3,"spaceBetween":24&#125;&#125;&#125;'>
					<div class="swiper-wrapper">
						{#each featured as review (review.id)}
							<div class="swiper-slide">
								<div class="testimonial-card-items-one">
									<p>{review.comment}</p>
									<div class="client-info-wrapper d-flex align-items-center justify-content-between">
										<div class="client-info">
											<div class="client-img bg-cover"
												style="background-image: url('{review.user?.avatar || review.product?.image || '/assets/images/testimonial/testimonialProfileThumb1_1.jpg'}');">
												<div class="icon">
													<img class="shape" src="/assets/images/shape/shape.svg" alt="img">
												</div>
											</div>
											<div class="content">
												<h3>{review.user?.name ?? 'Müştəri'}</h3>
												<span>{review.product?.title ?? ''}</span>
												<div class="star">
													{#each Array.from({ length: 5 }) as _, i}
														<i class={i < (review.rate ?? 5) ? 'fa-solid fa-star' : 'fa-regular fa-star'}></i>
													{/each}
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						{/each}
					</div>
				</div>
			</div>
		</div>
	</section>
{/if}

<style>
	.testimonial-section {
		padding-top: 56px;
		padding-bottom: 44px;
		background: linear-gradient(180deg, #f4fbf8 0%, #f7f8fb 100%);
		overflow: hidden;
	}

	.testimonial-section :global(.section-title) {
		margin-bottom: 24px;
	}

	.testimonial-section :global(.subtitle) {
		display: none;
	}

	.testimonial-section :global(.title) {
		margin: 0;
		font-size: clamp(26px, 3.4vw, 42px);
		line-height: 1.12;
		letter-spacing: 0;
	}

	.testimonial-slider-one {
		overflow: visible;
	}

	.testimonial-card-items-one {
		position: relative;
		display: flex;
		min-height: 258px;
		height: 100%;
		padding: 24px;
		flex-direction: column;
		justify-content: space-between;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 18px;
		background: rgba(255, 255, 255, 0.92);
		box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
		overflow: hidden;
	}

	.testimonial-card-items-one::before {
		content: '\201C';
		position: absolute;
		top: 10px;
		right: 18px;
		color: color-mix(in srgb, var(--theme) 16%, transparent);
		font-size: 84px;
		font-weight: 900;
		line-height: 1;
		pointer-events: none;
	}

	.testimonial-card-items-one p {
		position: relative;
		z-index: 1;
		display: -webkit-box;
		margin: 0 0 22px;
		overflow: hidden;
		color: #334155;
		font-size: 15px;
		line-height: 1.65;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 5;
		line-clamp: 5;
	}

	.client-info-wrapper {
		position: relative;
		z-index: 1;
		align-items: center !important;
		justify-content: flex-start !important;
		padding-top: 16px;
		border-top: 1px solid rgba(15, 23, 42, 0.08);
	}

	.client-info {
		display: grid;
		grid-template-columns: 52px minmax(0, 1fr);
		gap: 12px;
		align-items: center;
		min-width: 0;
	}

	.client-img {
		position: relative;
		width: 52px !important;
		height: 52px !important;
		border: 3px solid color-mix(in srgb, var(--theme) 18%, #fff);
		border-radius: 50%;
		background-size: cover;
		background-position: center;
		box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1);
	}

	.client-img .icon {
		display: none;
	}

	.content {
		min-width: 0;
	}

	.content h3 {
		margin: 0 0 2px;
		color: #111827;
		font-size: 15px;
		font-weight: 800;
		line-height: 1.2;
	}

	.content span {
		display: block;
		margin-bottom: 6px;
		color: #64748b;
		font-size: 12.5px;
		line-height: 1.25;
	}

	.star {
		display: inline-flex;
		gap: 3px;
		color: #f59e0b;
		font-size: 12px;
		line-height: 1;
	}

	@media (max-width: 575.98px) {
		.testimonial-section {
			padding-top: 34px;
			padding-bottom: 30px;
		}

		.testimonial-section :global(.container) {
			padding-right: 16px;
			padding-left: 16px;
		}

		.testimonial-section :global(.section-title) {
			margin-bottom: 16px;
		}

		.testimonial-card-items-one {
			min-height: 226px;
			padding: 18px;
			border-radius: 16px;
			box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
		}

		.testimonial-card-items-one p {
			margin-bottom: 16px;
			font-size: 13.5px;
			line-height: 1.55;
			-webkit-line-clamp: 4;
			line-clamp: 4;
		}

		.client-info-wrapper {
			padding-top: 12px;
		}

		.client-info {
			grid-template-columns: 46px minmax(0, 1fr);
			gap: 10px;
		}

		.client-img {
			width: 46px !important;
			height: 46px !important;
		}

		.content h3 {
			font-size: 14px;
		}
	}
</style>
