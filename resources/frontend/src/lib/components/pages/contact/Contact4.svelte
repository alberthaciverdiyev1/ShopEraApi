<script lang="ts">
	import { translate } from '$lib/i18n';
	import { sendContactMessage, type ApiContactInfo } from '$lib/services/contact';

	let { contactInfo }: { contactInfo?: ApiContactInfo } = $props();

	let firstName = $state('');
	let lastName = $state('');
	let email = $state('');
	let phone = $state('');
	let message = $state('');

	let submitting = $state(false);
	let successMessage = $state<string | null>(null);
	let errorMessage = $state<string | null>(null);


	const contactItems = $derived.by(() => {
		const items: Array<{
			icon: string;
			label: string;
			value: string;
			href: string;
			external?: boolean;
			phones?: string[];
		}> = [];

		const allPhones = contactInfo?.phones && contactInfo.phones.length > 0
			? contactInfo.phones
			: contactInfo?.phone
				? [contactInfo.phone]
				: [];

		if (allPhones.length > 0) {
			items.push({
				icon: 'fa-solid fa-phone',
				label: 'Phone Number',
				value: allPhones[0],
				href: `tel:${allPhones[0].replace(/\s+/g, '')}`,
				phones: allPhones
			});
		} else {
			items.push({
				icon: 'fa-solid fa-phone',
				label: 'Phone Number',
				value: '+994 (12) 555-00-00',
				href: 'tel:+994125550000'
			});
		}

		const storeEmail = contactInfo?.email?.trim() || 'support@snaker.store';
		items.push({
			icon: 'fa-regular fa-envelope',
			label: 'Email',
			value: storeEmail,
			href: `mailto:${storeEmail}`
		});

		const storeAddress = contactInfo?.address?.trim() || 'Bakı, Azərbaycan';
		items.push({
			icon: 'fa-solid fa-location-dot',
			label: 'Address',
			value: storeAddress,
			href: contactInfo?.google_map_url || `https://maps.google.com/?q=${encodeURIComponent(storeAddress)}`,
			external: true
		});

		return items;
	});

	const contactSocials = $derived.by(() => {
		const list: Array<{ label: string; icon: string; href: string }> = [];

		if (contactInfo?.whatsapp_number) {
			list.push({
				label: 'WhatsApp',
				icon: 'fa-brands fa-whatsapp',
				href: `https://wa.me/${contactInfo.whatsapp_number.replace(/\D/g, '')}`
			});
		}
		if (contactInfo?.instagram_url) {
			list.push({
				label: 'Instagram',
				icon: 'fa-brands fa-instagram',
				href: contactInfo.instagram_url
			});
		}
		if (contactInfo?.tiktok_url) {
			list.push({
				label: 'TikTok',
				icon: 'fa-brands fa-tiktok',
				href: contactInfo.tiktok_url
			});
		}
		if (contactInfo?.twitter_url) {
			list.push({
				label: 'X (Twitter)',
				icon: 'fa-brands fa-x-twitter',
				href: contactInfo.twitter_url
			});
		}
		if (contactInfo?.facebook_url) {
			list.push({
				label: 'Facebook',
				icon: 'fa-brands fa-facebook-f',
				href: contactInfo.facebook_url
			});
		}
		if (contactInfo?.telegram_url) {
			list.push({
				label: 'Telegram',
				icon: 'fa-brands fa-telegram',
				href: contactInfo.telegram_url
			});
		}
		if (contactInfo?.youtube_url) {
			list.push({
				label: 'YouTube',
				icon: 'fa-brands fa-youtube',
				href: contactInfo.youtube_url
			});
		}
		if (contactInfo?.linkedin_url) {
			list.push({
				label: 'LinkedIn',
				icon: 'fa-brands fa-linkedin-in',
				href: contactInfo.linkedin_url
			});
		}
		if (contactInfo?.google_map_url) {
			list.push({
				label: 'Google Maps',
				icon: 'fa-solid fa-map-location-dot',
				href: contactInfo.google_map_url
			});
		}

		if (list.length === 0) {
			return [
				{ label: 'Instagram', icon: 'fa-brands fa-instagram', href: 'https://www.instagram.com/snaker.store' },
				{ label: 'Facebook', icon: 'fa-brands fa-facebook-f', href: 'https://www.facebook.com/' },
				{ label: 'X (Twitter)', icon: 'fa-brands fa-x-twitter', href: 'https://x.com/' },
				{ label: 'LinkedIn', icon: 'fa-brands fa-linkedin-in', href: 'https://www.linkedin.com/' }
			];
		}

		return list;
	});

	async function handleSubmit(event: SubmitEvent) {
		event.preventDefault();
		if (submitting) return;

		errorMessage = null;
		successMessage = null;

		if (!firstName.trim()) {
			errorMessage = $translate('Please enter your first name.');
			return;
		}
		if (!email.trim()) {
			errorMessage = $translate('Please enter your email address.');
			return;
		}
		if (!message.trim()) {
			errorMessage = $translate('Please enter your message.');
			return;
		}

		submitting = true;
		try {
			await sendContactMessage({
				first_name: firstName.trim(),
				last_name: lastName.trim() || undefined,
				email: email.trim(),
				phone: phone.trim() || undefined,
				message: message.trim()
			});

			successMessage = $translate('Mesajınız uğurla göndərildi. Tezliklə sizinlə əlaqə saxlanılacaq.');
			firstName = '';
			lastName = '';
			email = '';
			phone = '';
			message = '';
		} catch (err: unknown) {
			errorMessage = err instanceof Error ? err.message : $translate('An error occurred. Please try again.');
		} finally {
			submitting = false;
		}
	}
</script>

<section class="contact-section">
	<div class="container">
		<div class="contact-shell">
			<aside class="contact-panel" aria-label={$translate('Contact Information')}>
				<div>
					<p class="eyebrow">{$translate('Contact')}</p>
					<h1>{$translate('Contact Information')}</h1>
					<p class="subtitle">{$translate('Say something to start a live chat!')}</p>
				</div>

				<ul class="contact-list">
					{#each contactItems as item}
						<li>
							<span class="contact-icon"><i class={item.icon}></i></span>
							<span>
								<span class="contact-label">{$translate(item.label)}</span>
								{#if item.phones && item.phones.length > 1}
									<span class="phone-group">
										{#each item.phones as p, idx}
											<a href={`tel:${p.replace(/\s+/g, '')}`}>{p}</a>{#if idx < item.phones.length - 1}<span>, </span>{/if}
										{/each}
									</span>
								{:else}
									<a
										href={item.href}
										target={item.external ? '_blank' : undefined}
										rel={item.external ? 'noopener noreferrer' : undefined}
									>
										{item.value}
									</a>
								{/if}
							</span>
						</li>
					{/each}
				</ul>

				<div class="social-row" aria-label={$translate('Social links')}>
					{#each contactSocials as social (social.label)}
						<a
							href={social.href}
							target="_blank"
							rel="noopener noreferrer"
							aria-label={social.label}
							title={social.label}
						>
							<i class={social.icon}></i>
						</a>
					{/each}
				</div>
			</aside>

			<form class="contact-form" onsubmit={handleSubmit}>
				{#if successMessage}
					<div class="contact-alert success" role="alert">
						<i class="fa-solid fa-circle-check"></i>
						<span>{successMessage}</span>
					</div>
				{/if}
				{#if errorMessage}
					<div class="contact-alert error" role="alert">
						<i class="fa-solid fa-circle-exclamation"></i>
						<span>{errorMessage}</span>
					</div>
				{/if}

				<div class="field-grid">
					<label>
						<span>{$translate('First Name')} <span class="required-star">*</span></span>
						<input
							type="text"
							name="first_name"
							autocomplete="given-name"
							placeholder={$translate('First Name')}
							bind:value={firstName}
							required
							disabled={submitting}
						/>
					</label>
					<label>
						<span>{$translate('Last Name')}</span>
						<input
							type="text"
							name="last_name"
							autocomplete="family-name"
							placeholder={$translate('Last Name')}
							bind:value={lastName}
							disabled={submitting}
						/>
					</label>
					<label>
						<span>{$translate('Email')} <span class="required-star">*</span></span>
						<input
							type="email"
							name="email"
							autocomplete="email"
							placeholder={$translate('Email')}
							bind:value={email}
							required
							disabled={submitting}
						/>
					</label>
					<label>
						<span>{$translate('Phone Number')}</span>
						<input
							type="tel"
							name="phone"
							autocomplete="tel"
							placeholder={$translate('Phone Number')}
							bind:value={phone}
							disabled={submitting}
						/>
					</label>
				</div>

				<label class="message-field">
					<span>{$translate('Message')} <span class="required-star">*</span></span>
					<textarea
						name="message"
						rows="6"
						placeholder={$translate('Write your message..')}
						bind:value={message}
						required
						disabled={submitting}
					></textarea>
				</label>

				<div class="form-actions">
					<button class="theme-btn style6" type="submit" disabled={submitting}>
						{#if submitting}
							<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
							{$translate('Sending...')}
						{:else}
							{$translate('Send Message')}
						{/if}
					</button>
				</div>
			</form>
		</div>
	</div>
</section>

<style>
	.contact-section {
		padding: 72px 0;
		background: #f8fafc;
	}

	.contact-shell {
		display: grid;
		grid-template-columns: minmax(280px, 0.88fr) minmax(0, 1.35fr);
		gap: 18px;
		overflow: hidden;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 20px;
		background: #ffffff;
		box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
	}

	.contact-panel {
		position: relative;
		display: flex;
		min-height: 560px;
		flex-direction: column;
		justify-content: space-between;
		padding: 42px;
		overflow: hidden;
		background: #0f172a;
		color: #ffffff;
	}

	.contact-panel::after {
		content: '';
		position: absolute;
		right: -110px;
		bottom: -120px;
		width: 280px;
		height: 280px;
		border-radius: 50%;
		background: rgba(22, 163, 74, 0.34);
	}

	.eyebrow {
		margin: 0 0 14px;
		color: #86efac;
		font-size: 13px;
		font-weight: 800;
		letter-spacing: 0.06em;
		text-transform: uppercase;
	}

	.contact-panel h1 {
		margin: 0;
		color: #ffffff;
		font-size: 34px;
		line-height: 1.12;
	}

	.subtitle {
		max-width: 360px;
		margin: 14px 0 0;
		color: rgba(255, 255, 255, 0.74);
		font-size: 15.5px;
		line-height: 1.6;
	}

	.contact-list {
		position: relative;
		z-index: 1;
		display: grid;
		gap: 22px;
		margin: 42px 0;
		padding: 0;
		list-style: none;
	}

	.contact-list li {
		display: grid;
		grid-template-columns: 42px minmax(0, 1fr);
		gap: 14px;
		align-items: center;
	}

	.contact-icon {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 42px;
		height: 42px;
		border: 1px solid rgba(255, 255, 255, 0.12);
		border-radius: 14px;
		background: rgba(255, 255, 255, 0.08);
		color: #86efac;
	}

	.contact-label {
		display: block;
		margin-bottom: 3px;
		color: rgba(255, 255, 255, 0.52);
		font-size: 12px;
		font-weight: 700;
	}

	.contact-list a {
		color: #ffffff;
		font-size: 15px;
		font-weight: 650;
		line-height: 1.35;
		text-decoration: none;
	}

	.contact-list a:hover {
		color: #86efac;
		text-decoration: underline;
	}

	.phone-group {
		color: #ffffff;
	}

	.social-row {
		position: relative;
		z-index: 1;
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
	}

	.social-row a {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: #ffffff;
		color: #0f172a;
		transition: color 0.18s ease, transform 0.18s ease;
	}

	.social-row a:hover {
		color: var(--theme);
		transform: translateY(-2px);
	}

	.contact-form {
		padding: 42px;
	}

	.contact-alert {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 14px 18px;
		margin-bottom: 24px;
		border-radius: 12px;
		font-size: 14.5px;
		font-weight: 600;
		line-height: 1.4;
	}

	.contact-alert.success {
		background: #ecfdf5;
		color: #065f46;
		border: 1px solid #a7f3d0;
	}

	.contact-alert.error {
		background: #fef2f2;
		color: #991b1b;
		border: 1px solid #fecaca;
	}

	.contact-alert i {
		font-size: 18px;
		shrink: 0;
	}

	.field-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 22px;
	}

	.contact-form label,
	.subject-group {
		display: grid;
		gap: 9px;
	}

	.contact-form label span,
	.subject-group legend {
		color: #0f172a;
		font-size: 13.5px;
		font-weight: 800;
	}

	.required-star {
		color: #dc2626;
		margin-left: 2px;
	}

	.contact-form input,
	.contact-form textarea {
		width: 100%;
		border: 1px solid #dbe3ef;
		border-radius: 12px;
		background: #f8fafc;
		color: #0f172a;
		font-size: 15px;
		outline: none;
		transition: border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
	}

	.contact-form input:disabled,
	.contact-form textarea:disabled {
		opacity: 0.7;
		cursor: not-allowed;
		background: #f1f5f9;
	}

	.contact-form input {
		height: 48px;
		padding: 0 14px;
	}

	.contact-form textarea {
		min-height: 150px;
		padding: 14px;
		resize: vertical;
	}

	.contact-form input:focus,
	.contact-form textarea:focus {
		border-color: rgba(var(--theme-rgb), 0.7);
		background: #ffffff;
		box-shadow: 0 0 0 4px rgba(var(--theme-rgb), 0.1);
	}

	.subject-group {
		margin: 26px 0;
		padding: 0;
		border: 0;
	}

	.subject-options {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 10px;
	}

	.subject-options label {
		display: flex;
		align-items: center;
		gap: 8px;
		min-height: 42px;
		padding: 0 12px;
		border: 1px solid #dbe3ef;
		border-radius: 999px;
		background: #ffffff;
		cursor: pointer;
		transition: border-color 0.15s ease, background-color 0.15s ease;
	}

	.subject-options label.active {
		border-color: var(--theme);
		background: rgba(var(--theme-rgb), 0.05);
	}

	.subject-options input {
		width: 15px;
		height: 15px;
		accent-color: var(--theme);
		cursor: pointer;
	}

	.subject-options span {
		font-size: 12.5px !important;
		font-weight: 700 !important;
		white-space: nowrap;
	}

	.form-actions {
		display: flex;
		justify-content: flex-end;
		margin-top: 22px;
	}

	.form-actions :global(.theme-btn) {
		border: 0;
		border-radius: 999px;
		padding: 15px 30px !important;
		cursor: pointer;
	}

	.form-actions :global(.theme-btn:disabled) {
		opacity: 0.65;
		cursor: not-allowed;
	}

	@media (max-width: 1199.98px) {
		.contact-shell {
			grid-template-columns: 1fr;
		}

		.contact-panel {
			min-height: auto;
		}
	}

	@media (max-width: 767.98px) {
		.contact-section {
			padding: 28px 0 96px;
		}

		.contact-shell {
			border-radius: 16px;
		}

		.contact-panel,
		.contact-form {
			padding: 24px;
		}

		.contact-panel h1 {
			font-size: 26px;
		}

		.field-grid,
		.subject-options {
			grid-template-columns: 1fr;
		}

		.subject-options span {
			white-space: normal;
		}

		.form-actions,
		.form-actions :global(.theme-btn) {
			width: 100%;
		}
	}
</style>
