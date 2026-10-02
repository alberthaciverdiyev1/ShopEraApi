<script lang="ts">
	// Shared page breadcrumb (every inner page uses the same markup, only the
	// trail differs).
	import { translate } from '$lib/i18n';

	let { title = '', parent = 'Category' }: { title?: string; parent?: string } = $props();
</script>

<!-- Breadcrumb Section -->
<div class="breadcumb-section">
	<div class="breadcumb-container-wrapper" data-bg-src="/assets/images/breadcumb/breadcumb-bg.png">
		<div class="shape1 d-none d-lg-block"><img src="/assets/images/shape/breadcumb-shape1_1.png" alt="shape" /></div>
		<div class="shape2 d-none d-lg-block"><img src="/assets/images/shape/breadcumb-shape1_2.png" alt="shape" /></div>
		<div class="shape3 d-none d-lg-block"><img src="/assets/images/shape/breadcumb-shape1_3.png" alt="shape" /></div>
		<div class="shape4 d-none d-lg-block"><img src="/assets/images/shape/breadcumb-shape1_4.png" alt="shape" /></div>
		<div class="container">
			<ul class="breadcumb-wrapper">
				<li>
					<a href="/" aria-label={$translate('Home')}>
						<i class="fa-sharp fa-light fa-house"></i>
					</a>
				</li>
				{#if parent}
					<li><i class="fa-solid fa-chevron-right separator-icon"></i></li>
					<li class="parent-crumb">{$translate(parent)}</li>
				{/if}
				{#if title}
					<li><i class="fa-solid fa-chevron-right separator-icon"></i></li>
					<li class="active-crumb">{$translate(title)}</li>
				{/if}
			</ul>
		</div>
	</div>
</div>

<style>
	.breadcumb-section {
		overflow: hidden;
		background: #f8fafc;
		border-bottom: 1px solid rgba(0, 0, 0, 0.05);
	}

	.breadcumb-container-wrapper {
		position: relative;
	}

	.breadcumb-wrapper {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 10px;
		padding: 28px 0;
		margin: 0;
		list-style: none;
		font-size: 15px;
		font-family: 'Albert Sans', sans-serif;
	}

	.breadcumb-wrapper li {
		display: inline-flex;
		align-items: center;
		color: #64748b;
	}

	.breadcumb-wrapper li a {
		color: #64748b;
		text-decoration: none;
		transition: color 0.2s ease;
	}

	.breadcumb-wrapper li a:hover {
		color: var(--theme, #ed0006);
	}

	.separator-icon {
		font-size: 10px;
		color: #94a3b8;
		opacity: 0.7;
	}

	.active-crumb {
		color: var(--theme, #ed0006) !important;
		font-weight: 600;
	}

	/* Mobile responsive optimization */
	@media (max-width: 767.98px) {
		.breadcumb-section {
			border-bottom: 1px solid rgba(15, 23, 42, 0.06);
		}

		.breadcumb-container-wrapper {
			min-height: 0 !important;
			padding: 0 !important;
			background-size: cover;
		}

		.breadcumb-container-wrapper :global(.container) {
			padding-right: 16px;
			padding-left: 16px;
		}

		.breadcumb-wrapper {
			padding: 8px 0 !important;
			gap: 5px !important;
			font-size: 11.5px !important;
			justify-content: center !important;
			overflow-x: auto;
			white-space: nowrap;
			-webkit-overflow-scrolling: touch;
			scrollbar-width: none;
		}

		.breadcumb-wrapper::-webkit-scrollbar {
			display: none;
		}

		.separator-icon {
			font-size: 8px !important;
		}

		.breadcumb-wrapper li a {
			width: 24px;
			height: 24px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
		}

		:global(.breadcumb-container-wrapper .shape1),
		:global(.breadcumb-container-wrapper .shape2),
		:global(.breadcumb-container-wrapper .shape3),
		:global(.breadcumb-container-wrapper .shape4) {
			display: none !important;
		}
	}
</style>
