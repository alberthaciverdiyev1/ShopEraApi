<script lang="ts">
	import { features } from '$lib/services/features';
	import { translate } from '$lib/i18n';
	import {
		mailHref,
		phoneHref,
		primaryPhone,
		settingAddress,
		settingEmail,
		settingSocialLinks,
		settings
	} from '$lib/services/settings';

	let email = $state('');
	let subscribed = $state(false);
	let error = $state<string | null>(null);
	const footerPhone = $derived(primaryPhone($settings));
	const footerEmail = $derived(settingEmail($settings));
	const footerAddress = $derived(settingAddress($settings));
	const footerSocials = $derived(settingSocialLinks($settings));

	function handleSubscribe(e: SubmitEvent) {
		e.preventDefault();
		const trimmed = email.trim();
		if (!trimmed || !trimmed.includes('@')) {
			error = $translate('Please enter a valid email address.');
			return;
		}
		error = null;
		subscribed = true;
		email = '';
	}

	const currentYear = new Date().getFullYear();
</script>

<footer class="shopera-footer">
	<!-- Main Footer Columns -->
	<div class="footer-main">
		<div class="container">
			<div class="row g-4 g-xl-5">
				<!-- Brand Column -->
				<div class="col-lg-4 col-md-6">
					<div class="footer-brand-box">
						<a href="/" class="footer-logo d-inline-block mb-3">
							<img src="/assets/images/logo/white-logo.svg" alt="ShopEra Logo" width="165" height="32" />
						</a>
						<p class="brand-desc mb-4">
							{$translate('ShopEra brings together the latest collections of electronics, fashion, home, and lifestyle products in a modern ecommerce platform.')}
						</p>

						<div class="contact-compact mb-4">
							<div class="contact-line">
								<i class="fa-solid fa-phone"></i>
								<a href={phoneHref(footerPhone)}>{footerPhone}</a>
							</div>
							<div class="contact-line">
								<i class="fa-solid fa-envelope"></i>
								<a href={mailHref(footerEmail)}>{footerEmail}</a>
							</div>
							<div class="contact-line">
								<i class="fa-solid fa-location-dot"></i>
								<span>{footerAddress}</span>
							</div>
						</div>

						{#if footerSocials.length}
							<div class="social-links d-flex align-items-center flex-wrap gap-2">
								{#each footerSocials as social (social.label)}
									<a href={social.href} target="_blank" rel="noopener noreferrer" aria-label={social.label} title={social.label} class="social-btn">
										<i class={social.icon}></i>
									</a>
								{/each}
							</div>
						{/if}
					</div>
				</div>

				<!-- Categories Column -->
				<div class="col-lg-2 col-6">
					<div class="footer-widget">
						<h5 class="widget-title">{$translate('Categories')}</h5>
						<ul class="widget-links">
							<li><a href="/shop?category=1"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('Electronics')}</a></li>
							<li><a href="/shop?category=2"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('Fashion')}</a></li>
							<li><a href="/shop?category=3"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('Home & Living')}</a></li>
							<li><a href="/shop?category=4"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('Beauty')}</a></li>
							<li><a href="/shop?category=5"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('Sports')}</a></li>
							<li><a href="/shop"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('All products')}</a></li>
						</ul>
					</div>
				</div>

				<!-- Customer Service Column -->
				<div class="col-lg-2 col-6">
					<div class="footer-widget">
						<h5 class="widget-title">{$translate('Customer Service')}</h5>
						<ul class="widget-links">
							<li><a href="/about"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('About')}</a></li>
							<li><a href="/order/tracking"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('Track Order')}</a></li>
							<li><a href="/order/history"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('Order History')}</a></li>
							{#if $features.favorites !== false}<li><a href="/wishlist"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('Wishlist')}</a></li>{/if}
							{#if $features.faq !== false}<li><a href="/faq"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('FAQ')}</a></li>{/if}
							<li><a href="/contact"><i class="fa-solid fa-chevron-right link-arrow"></i> {$translate('Contact')}</a></li>
						</ul>
					</div>
				</div>

				<!-- Newsletter & Security Column -->
				<div class="col-lg-4 col-md-12">
					<div class="footer-widget newsletter-widget">
						<h5 class="widget-title">{$translate('Stay Updated')}</h5>
						<p class="widget-desc mb-3">
							{$translate('Be the first to hear about special campaigns, discounts, and new products.')}
						</p>

						{#if subscribed}
							<div class="alert alert-success d-flex align-items-center py-2 px-3 rounded-3 mb-3">
								<i class="fa-solid fa-circle-check me-2 fs-5"></i>
								<span class="small fw-semibold">{$translate('Thanks! You are subscribed.')}</span>
							</div>
						{:else}
							<form onsubmit={handleSubscribe} class="newsletter-form mb-2">
								<div class="input-wrap">
									<input
										type="email"
										placeholder={$translate('Enter your email address')}
										bind:value={email}
										class="newsletter-input"
										required
									/>
									<button type="submit" class="newsletter-submit" aria-label={$translate('Subscribe')}>
										<i class="fa-solid fa-paper-plane"></i>
									</button>
								</div>
								{#if error}
									<div class="small text-danger mt-1">{error}</div>
								{/if}
							</form>
							<span class="small text-muted d-block privacy-hint">
								<i class="fa-solid fa-lock me-1"></i> {$translate('Your email is never shared.')}
							</span>
						{/if}

					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Bottom Copyright & Payment Methods -->
	<div class="footer-bottom">
		<div class="container">
			<div class="row align-items-center gy-3">
				<div class="col-md-5 text-center text-md-start">
					<p class="copyright-text mb-0">
						© {currentYear} <a href="/" class="text-white fw-bold text-decoration-none">ShopEra</a>. {$translate('All rights reserved.')}
					</p>
				</div>
				<div class="col-md-3 text-center">
					<div class="legal-links d-inline-flex gap-3">
						{#if $features.legal_terms !== false}<a href="/terms">{$translate('Terms')}</a>{/if}
						<span class="text-secondary opacity-50">•</span>
						{#if $features.legal_terms !== false}<a href="/privacy">{$translate('Privacy')}</a>{/if}
						<span class="text-secondary opacity-50">•</span>
						{#if $features.faq !== false}<a href="/faq">FAQ</a>{/if}
					</div>
				</div>
			</div>
		</div>
	</div>
</footer>

<style>
	.shopera-footer {
		background: linear-gradient(180deg, #111827 0%, #0a0e17 100%);
		color: #94a3b8;
		font-family: 'Albert Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
		position: relative;
		overflow: hidden;
		border-top: 1px solid rgba(255, 255, 255, 0.08);
	}


	/* Main Columns */
	.footer-main {
		padding: 56px 0 44px;
	}

	.footer-brand-box .brand-desc {
		font-size: 14px;
		line-height: 1.65;
		color: #94a3b8;
		max-width: 340px;
	}

	.contact-compact {
		display: flex;
		flex-direction: column;
		gap: 9px;
	}

	.contact-line {
		display: flex;
		align-items: center;
		gap: 10px;
		font-size: 13.5px;
		color: #cbd5e1;
	}

	.contact-line i {
		color: var(--theme);
	}

	.contact-line a {
		color: #cbd5e1;
		text-decoration: none;
		transition: color 0.2s ease;
	}

	.contact-line a:hover {
		color: var(--theme, #16A34A);
	}

	/* Social buttons */
	.social-btn {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.06);
		border: 1px solid rgba(255, 255, 255, 0.08);
		color: #cbd5e1;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 14px;
		text-decoration: none;
		transition: all 0.25s ease;
	}

	.social-btn:hover {
		background: var(--theme, #16A34A);
		border-color: var(--theme, #16A34A);
		color: #ffffff;
		transform: translateY(-3px);
		box-shadow: 0 6px 14px rgba(var(--theme-rgb), 0.35);
	}

	/* Widget Titles */
	.widget-title {
		color: #ffffff;
		font-size: 17px;
		font-weight: 700;
		margin-bottom: 22px;
		position: relative;
		padding-bottom: 12px;
	}

	.widget-title::after {
		content: '';
		position: absolute;
		bottom: 0;
		left: 0;
		width: 32px;
		height: 2px;
		background: var(--theme, #16A34A);
		border-radius: 2px;
	}

	/* Widget Links */
	.widget-links {
		list-style: none;
		padding: 0;
		margin: 0;
		display: flex;
		flex-direction: column;
		gap: 11px;
	}

	.widget-links li a {
		color: #94a3b8;
		text-decoration: none;
		font-size: 14px;
		display: inline-flex;
		align-items: center;
		transition: all 0.2s ease;
	}

	.link-arrow {
		font-size: 10px;
		margin-right: 8px;
		color: rgba(255, 255, 255, 0.25);
		transition: all 0.2s ease;
	}

	.widget-links li a:hover {
		color: #ffffff;
		transform: translateX(4px);
	}

	.widget-links li a:hover .link-arrow {
		color: var(--theme, #16A34A);
		transform: translateX(2px);
	}

	/* Newsletter */
	.widget-desc {
		font-size: 13.5px;
		line-height: 1.6;
		color: #94a3b8;
	}

	.newsletter-form {
		width: 100%;
	}

	.input-wrap {
		position: relative;
		display: flex;
		align-items: center;
	}

	.newsletter-input {
		width: 100%;
		height: 48px;
		padding: 0 52px 0 16px;
		background: rgba(255, 255, 255, 0.05);
		border: 1px solid rgba(255, 255, 255, 0.12);
		border-radius: 10px;
		color: #ffffff;
		font-size: 13.5px;
		outline: none;
		transition: border-color 0.2s ease, background 0.2s ease;
	}

	.newsletter-input:focus {
		background: rgba(255, 255, 255, 0.08);
		border-color: var(--theme, #16A34A);
	}

	.newsletter-input::placeholder {
		color: #64748b;
	}

	.newsletter-submit {
		position: absolute;
		right: 6px;
		top: 6px;
		bottom: 6px;
		width: 38px;
		border: none;
		border-radius: 8px;
		background: var(--theme, #16A34A);
		color: #ffffff;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: background 0.2s ease, transform 0.15s ease;
	}

	.newsletter-submit:hover {
		background: color-mix(in srgb, var(--theme) 88%, #000);
		transform: scale(1.05);
	}

	.privacy-hint {
		font-size: 11.5px;
		color: #64748b;
	}

	/* Bottom Bar */
	.footer-bottom {
		padding: 22px 0;
		border-top: 1px solid rgba(255, 255, 255, 0.07);
		background: rgba(0, 0, 0, 0.25);
		font-size: 13px;
	}

	.copyright-text {
		color: #64748b;
	}

	.legal-links a {
		color: #94a3b8;
		text-decoration: none;
		font-size: 12.5px;
		transition: color 0.2s ease;
	}

	.legal-links a:hover {
		color: #ffffff;
	}

	@media (max-width: 991.98px) {
		.footer-main {
			padding: 40px 0 20px;
		}

		.footer-bottom {
			padding-bottom: calc(78px + env(safe-area-inset-bottom));
		}

		.widget-title {
			margin-bottom: 16px;
		}
	}
</style>
