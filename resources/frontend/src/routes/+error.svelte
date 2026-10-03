<script lang="ts">
	import { page } from '$app/state';
	import { translate } from '$lib/i18n';

	const titles: Record<number, string> = {
		404: 'Page not found',
		403: 'Access denied',
		500: 'Something went wrong'
	};
	const messages: Record<number, string> = {
		404: 'The page you are looking for does not exist or has moved.',
		403: 'You do not have permission to view this page.',
		500: 'An unexpected error occurred. Please try again in a moment.'
	};

	const status = $derived(page.status);
	const titleKey = $derived(titles[status] ?? 'Something went wrong');
	const messageKey = $derived(
		messages[status] ?? 'An unexpected error occurred. Please try again in a moment.'
	);
</script>

<svelte:head>
	<title>{status} — {$translate(titleKey)}</title>
	<meta name="robots" content="noindex" />
</svelte:head>

<section class="error-page fix section-padding">
	<div class="container">
		<div class="error-card">
			<div class="error-code">{status}</div>
			<h2>{$translate(titleKey)}</h2>
			<p>{$translate(messageKey)}</p>
			<div class="error-actions">
				<a class="theme-btn style6" href="/">{$translate('Back to home')}</a>
				<button type="button" class="theme-btn style6 error-outline" onclick={() => history.back()}>
					{$translate('Go back')}
				</button>
			</div>
		</div>
	</div>
</section>

<style>
	.error-page {
		background:
			radial-gradient(circle at 20% 0%, color-mix(in srgb, var(--theme) 12%, transparent), transparent 38%),
			#f7f8fb;
	}
	.error-card {
		max-width: 560px;
		margin: 0 auto;
		text-align: center;
		background: #fff;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 24px;
		padding: 48px 32px;
		box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
	}
	.error-code {
		font-size: 84px;
		font-weight: 900;
		line-height: 1;
		letter-spacing: -2px;
		background: linear-gradient(120deg, var(--theme, #06b6d4), #6366f1);
		-webkit-background-clip: text;
		background-clip: text;
		color: transparent;
	}
	.error-card h2 {
		margin: 18px 0 8px;
		font-size: 24px;
		font-weight: 800;
		color: #0f172a;
	}
	.error-card p {
		margin: 0 0 26px;
		color: #64748b;
		font-size: 15px;
		line-height: 1.6;
	}
	.error-actions {
		display: flex;
		flex-wrap: wrap;
		gap: 12px;
		justify-content: center;
	}
	.error-outline {
		background: #fff !important;
		color: #0f172a !important;
		border: 1px solid rgba(15, 23, 42, 0.14);
	}
	.error-outline:hover {
		background: #f1f5f9 !important;
	}
</style>
