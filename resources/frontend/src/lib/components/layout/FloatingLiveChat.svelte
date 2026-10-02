<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import { user, isLoggedIn } from '$lib/services/auth';
	import { translate } from '$lib/i18n';
	import {
		chatMessages,
		chatLoading,
		chatSending,
		chatError,
		loadChatMessages,
		sendChatMessage,
		markChatRead,
		deleteChatMessage
	} from '$lib/services/chat';

	let isOpen = $state(false);
	let showTeaser = $state(true);
	let text = $state('');
	let attachment = $state<File | null>(null);
	let attachmentPreview = $state<string | null>(null);
	let scrollContainer = $state<HTMLDivElement | null>(null);
	let fileInput = $state<HTMLInputElement | null>(null);

	let pollTimer: ReturnType<typeof setInterval> | undefined;
	let prevMessageCount = $state(0);

	const currentUserId = $derived($user?.id ?? null);

	const unreadAdminCount = $derived(
		$isLoggedIn && Array.isArray($chatMessages)
			? $chatMessages.filter((m) => m.sender_type === 'admin' && !m.is_read).length
			: 0
	);

	function playNotificationSound() {
		try {
			const AudioContextClass =
				window.AudioContext ||
				(window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
			if (!AudioContextClass) return;
			const ctx = new AudioContextClass();
			const osc = ctx.createOscillator();
			const gain = ctx.createGain();
			osc.type = 'sine';
			osc.frequency.setValueAtTime(587.33, ctx.currentTime);
			osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12);
			gain.gain.setValueAtTime(0.12, ctx.currentTime);
			gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
			osc.connect(gain);
			gain.connect(ctx.destination);
			osc.start();
			osc.stop(ctx.currentTime + 0.35);
		} catch {
			// Best-effort audio playback
		}
	}

	function scrollToBottom(behavior: ScrollBehavior = 'smooth') {
		if (scrollContainer) {
			scrollContainer.scrollTo({
				top: scrollContainer.scrollHeight,
				behavior
			});
		}
	}

	function formatTime(value: string): string {
		const date = new Date(value);
		if (Number.isNaN(date.getTime())) return '';
		return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
	}

	function formatDate(value: string): string {
		const date = new Date(value);
		if (Number.isNaN(date.getTime())) return '';
		return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
	}

	async function refreshMessages({ markRead = false }: { markRead?: boolean } = {}) {
		if (!$isLoggedIn) return;

		const list = await loadChatMessages();
		if (list.length > prevMessageCount) {
			const hasNewAdminMsg = list.some(
				(m) => m.sender_type === 'admin' && !m.is_read
			);
			if (hasNewAdminMsg && !isOpen) {
				playNotificationSound();
			}
			prevMessageCount = list.length;
			if (isOpen) {
				setTimeout(() => scrollToBottom('smooth'), 60);
			}
		}

		if (markRead && list.length > 0) {
			const unreadAdmin = list.find(
				(m) => m.sender_type === 'admin' && !m.is_read
			);
			if (unreadAdmin) {
				await markChatRead(unreadAdmin.conversation_id);
			}
		}
	}

	function toggleChat() {
		isOpen = !isOpen;
		if (isOpen) {
			showTeaser = false;
			if ($isLoggedIn) {
				refreshMessages({ markRead: true });
				setTimeout(() => scrollToBottom('auto'), 80);
			}
		}
	}

	function closeChat() {
		isOpen = false;
	}

	function pickAttachment(event: Event) {
		const input = event.target as HTMLInputElement;
		const file = input.files?.[0] ?? null;
		if (attachmentPreview) URL.revokeObjectURL(attachmentPreview);
		attachment = file;
		attachmentPreview = file ? URL.createObjectURL(file) : null;
	}

	function clearAttachment() {
		if (attachmentPreview) URL.revokeObjectURL(attachmentPreview);
		attachment = null;
		attachmentPreview = null;
		if (fileInput) fileInput.value = '';
	}

	async function handleSend() {
		const val = text.trim();
		if (!val && !attachment) return;

		try {
			await sendChatMessage(val, attachment);
			text = '';
			clearAttachment();
			await refreshMessages({ markRead: false });
			setTimeout(() => scrollToBottom('smooth'), 60);
		} catch {
			// chatError store holds the error
		}
	}

	async function handleDelete(messageId: number) {
		if (!confirm($translate('Delete message') + '?')) return;
		try {
			await deleteChatMessage(messageId);
		} catch {
			// Best-effort
		}
	}

	function handleKeydown(event: KeyboardEvent) {
		if (event.key === 'Enter' && !event.shiftKey) {
			event.preventDefault();
			handleSend();
		}
	}

	onMount(() => {
		if ($isLoggedIn) {
			refreshMessages();
		}

		pollTimer = setInterval(() => {
			if ($isLoggedIn) {
				refreshMessages({ markRead: isOpen });
			}
		}, 8000);

		const teaserTimeout = setTimeout(() => {
			showTeaser = false;
		}, 12000);

		return () => {
			clearTimeout(teaserTimeout);
			if (pollTimer) clearInterval(pollTimer);
		};
	});

	onDestroy(() => {
		if (pollTimer) clearInterval(pollTimer);
		if (attachmentPreview) URL.revokeObjectURL(attachmentPreview);
	});

	$effect(() => {
		if (isOpen && $chatMessages.length) {
			scrollToBottom('smooth');
		}
	});
</script>

<!-- Floating Live Chat Trigger Button -->
<div class="floating-chat-container">
	{#if showTeaser && !isOpen && unreadAdminCount === 0}
		<div class="chat-teaser-bubble">
			<button
				type="button"
				class="teaser-close"
				onclick={(e) => {
					e.stopPropagation();
					showTeaser = false;
				}}
				aria-label={$translate('Bağla')}
			>
				&times;
			</button>
			<div class="teaser-text" onclick={toggleChat} role="button" tabindex="0" onkeydown={(e) => e.key === 'Enter' && toggleChat()}>
				<span class="teaser-title">{$translate('Live Support')}</span>
				<span class="teaser-desc">{$translate('Say something to start a live chat!')}</span>
			</div>
		</div>
	{/if}

	<button
		type="button"
		class="chat-floating-btn"
		class:active={isOpen}
		onclick={toggleChat}
		aria-label={$translate('Live Support')}
		title={$translate('Live Support')}
	>
		{#if unreadAdminCount > 0 && !isOpen}
			<span class="unread-badge">{unreadAdminCount}</span>
			<span class="pulse-ring"></span>
		{/if}

		{#if isOpen}
			<i class="fa-solid fa-xmark"></i>
		{:else}
			<i class="fa-solid fa-headset"></i>
		{/if}
	</button>
</div>

<!-- Floating Chat Window / Modal -->
{#if isOpen}
	<div class="chat-window-backdrop" onclick={closeChat} role="presentation"></div>

	<div class="chat-window">
		<!-- Window Header -->
		<div class="chat-header">
			<div class="header-info">
				<div class="support-avatar-wrapper">
					<div class="support-avatar">
						<i class="fa-solid fa-headset"></i>
					</div>
					<span class="online-dot"></span>
				</div>
				<div class="support-meta">
					<h4 class="support-title">{$translate('Live Support')}</h4>
					<p class="support-status">
						<span class="status-indicator"></span>
						{$translate('Online')} • {$translate('We typically reply in a few minutes')}
					</p>
				</div>
			</div>

			<div class="header-actions">
				{#if $isLoggedIn}
					<button
						type="button"
						class="header-btn"
						title={$translate('Yenilə')}
						onclick={() => refreshMessages({ markRead: true })}
						aria-label="Yenilə"
					>
						<i class="fa-solid fa-rotate" class:spin={$chatLoading}></i>
					</button>
				{/if}
				<button
					type="button"
					class="header-btn close-btn"
					onclick={closeChat}
					title={$translate('Close')}
					aria-label={$translate('Close')}
				>
					<i class="fa-solid fa-xmark"></i>
				</button>
			</div>
		</div>

		<!-- Window Body -->
		{#if !$isLoggedIn}
			<!-- Guest State Prompt -->
			<div class="guest-chat-card">
				<div class="guest-icon">
					<i class="fa-solid fa-comments"></i>
				</div>
				<h4 class="guest-title">{$translate('Live Support')}</h4>
				<p class="guest-desc">
					{$translate('Please log in to chat with our support team.')}
				</p>

				<div class="guest-actions">
					<a href="/login" class="guest-btn-primary" onclick={closeChat}>
						<i class="fa-solid fa-arrow-right-to-bracket"></i>
						<span>{$translate('Login')}</span>
					</a>
					<a href="/register" class="guest-btn-secondary" onclick={closeChat}>
						{$translate('Register')}
					</a>
				</div>

				<div class="guest-footer-info">
					<i class="fa-solid fa-shield-halved"></i>
					<span>{$translate('7/24 Müştəri Xidmətləri')}</span>
				</div>
			</div>
		{:else}
			<!-- Authenticated Chat Messages Stream -->
			<div class="chat-messages-container" bind:this={scrollContainer}>
				{#if $chatLoading && $chatMessages.length === 0}
					<div class="chat-loading-state">
						<div class="chat-spinner"></div>
						<p>{$translate('Loading...')}</p>
					</div>
				{:else if $chatMessages.length === 0}
					<div class="chat-empty-state">
						<div class="empty-icon">
							<i class="fa-solid fa-headset"></i>
						</div>
						<h5>{$translate('Salam! 👋')}</h5>
						<p>{$translate('Say something to start a live chat!')}</p>
					</div>
				{:else}
					{#each $chatMessages as message, index (message.id)}
						{@const isUser = message.sender_type === 'user' || message.sender_id === currentUserId}
						{@const showDate =
							index === 0 ||
							formatDate(message.created_at) !== formatDate($chatMessages[index - 1]?.created_at)}

						{#if showDate}
							<div class="chat-date-separator">
								<span>{formatDate(message.created_at)}</span>
							</div>
						{/if}

						<div class="message-row" class:is-user={isUser}>
							{#if !isUser}
								<div class="sender-avatar" title={$translate('Support')}>
									<i class="fa-solid fa-headset"></i>
								</div>
							{/if}

							<div class="bubble-wrapper">
								{#if !isUser}
									<span class="sender-name">{$translate('Support')}</span>
								{/if}

								<div class="message-bubble">
									{#if message.message}
										<p class="bubble-text">{message.message}</p>
									{/if}

									{#if message.attachments?.length}
										<div class="bubble-attachments">
											{#each message.attachments as att (att.id)}
												<a
													href={att.url}
													target="_blank"
													rel="noopener noreferrer"
													class="att-image-link"
												>
													<img src={att.url} alt={$translate('Qoşma')} loading="lazy" />
												</a>
											{/each}
										</div>
									{/if}

									<div class="bubble-footer">
										<span class="bubble-time">{formatTime(message.created_at)}</span>
										{#if isUser}
											<span class="msg-status" title={message.is_read ? 'Oxundu' : 'Göndərildi'}>
												{#if message.is_read}
													<i class="fa-solid fa-check-double text-read"></i>
												{:else}
													<i class="fa-solid fa-check"></i>
												{/if}
											</span>
											<button
												type="button"
												class="msg-delete-btn"
												title={$translate('Delete message')}
												onclick={() => handleDelete(message.id)}
											>
												<i class="fa-regular fa-trash-can"></i>
											</button>
										{/if}
									</div>
								</div>
							</div>
						</div>
					{/each}
				{/if}

				{#if $chatSending}
					<div class="message-row is-user sending-placeholder">
						<div class="bubble-wrapper">
							<div class="message-bubble sending">
								<span class="typing-dot"></span>
								<span class="typing-dot"></span>
								<span class="typing-dot"></span>
							</div>
						</div>
					</div>
				{/if}
			</div>

			<!-- Window Footer Composer -->
			<div class="chat-composer">
				{#if attachmentPreview}
					<div class="composer-preview">
						<img src={attachmentPreview} alt={$translate('Qoşma önbaxış')} />
						<span class="preview-name">{attachment?.name}</span>
						<button type="button" class="preview-remove" onclick={clearAttachment} aria-label={$translate('Ləğv et')}>
							<i class="fa-solid fa-xmark"></i>
						</button>
					</div>
				{/if}

				{#if $chatError}
					<div class="composer-error">
						<i class="fa-solid fa-circle-exclamation"></i>
						<span>{$chatError}</span>
					</div>
				{/if}

				<div class="composer-form">
					<input
						type="file"
						accept="image/*"
						bind:this={fileInput}
						onchange={pickAttachment}
						class="d-none"
						id="chat-file-input"
					/>
					<button
						type="button"
						class="composer-btn-attach"
						title={$translate('Attach image')}
						onclick={() => fileInput?.click()}
					>
						<i class="fa-solid fa-paperclip"></i>
					</button>

					<textarea
						rows="1"
						class="composer-input"
						placeholder={$translate('Type a message...')}
						bind:value={text}
						onkeydown={handleKeydown}
					></textarea>

					<button
						type="button"
						class="composer-btn-send"
						disabled={$chatSending || (!text.trim() && !attachment)}
						onclick={handleSend}
						title={$translate('Send Message')}
					>
						{#if $chatSending}
							<i class="fa-solid fa-spinner fa-spin"></i>
						{:else}
							<i class="fa-solid fa-paper-plane"></i>
						{/if}
					</button>
				</div>
			</div>
		{/if}
	</div>
{/if}

<style>
	/* Floating Launcher Container */
	.floating-chat-container {
		position: fixed;
		bottom: 24px;
		right: 24px;
		z-index: 996;
		display: flex;
		flex-direction: column;
		align-items: flex-end;
		gap: 10px;
	}

	@media (max-width: 991.98px) {
		.floating-chat-container {
			bottom: calc(72px + env(safe-area-inset-bottom, 0px));
			right: 18px;
		}
	}

	/* Teaser tooltip */
	.chat-teaser-bubble {
		position: relative;
		background: #ffffff;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 16px;
		box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.06);
		padding: 12px 32px 12px 16px;
		max-width: 250px;
		cursor: pointer;
		animation: teaserSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
		transition: transform 0.2s ease, box-shadow 0.2s ease;
	}

	.chat-teaser-bubble:hover {
		transform: translateY(-2px);
		box-shadow: 0 14px 30px -5px rgba(0, 0, 0, 0.16);
	}

	.chat-teaser-bubble::after {
		content: '';
		position: absolute;
		bottom: -7px;
		right: 22px;
		width: 14px;
		height: 14px;
		background: #ffffff;
		transform: rotate(45deg);
		border-right: 1px solid rgba(15, 23, 42, 0.08);
		border-bottom: 1px solid rgba(15, 23, 42, 0.08);
	}

	.teaser-close {
		position: absolute;
		top: 6px;
		right: 8px;
		background: transparent;
		border: none;
		font-size: 16px;
		line-height: 1;
		color: #94a3b8;
		cursor: pointer;
		padding: 2px 4px;
	}

	.teaser-close:hover {
		color: #0f172a;
	}

	.teaser-text {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}

	.teaser-title {
		font-size: 13px;
		font-weight: 700;
		color: #0f172a;
	}

	.teaser-desc {
		font-size: 11px;
		color: #64748b;
		line-height: 1.3;
	}

	/* Floating Button */
	.chat-floating-btn {
		position: relative;
		width: 58px;
		height: 58px;
		border-radius: 50%;
		border: none;
		background: linear-gradient(135deg, var(--theme, #fd3d57) 0%, color-mix(in srgb, var(--theme, #fd3d57) 80%, #000) 100%);
		color: #ffffff;
		font-size: 24px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		box-shadow: 0 8px 24px -2px rgba(253, 61, 87, 0.45), 0 4px 12px rgba(0, 0, 0, 0.12);
		transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
	}

	.chat-floating-btn:hover {
		transform: scale(1.08);
		box-shadow: 0 12px 28px -2px rgba(253, 61, 87, 0.55), 0 6px 16px rgba(0, 0, 0, 0.18);
	}

	.chat-floating-btn:active {
		transform: scale(0.95);
	}

	.chat-floating-btn.active {
		background: #1e293b;
		box-shadow: 0 8px 20px rgba(30, 41, 59, 0.4);
	}

	@media (max-width: 991.98px) {
		.chat-floating-btn {
			width: 52px;
			height: 52px;
			font-size: 21px;
		}
	}

	.unread-badge {
		position: absolute;
		top: -3px;
		right: -3px;
		background: #ef4444;
		color: #ffffff;
		font-size: 11px;
		font-weight: 800;
		min-width: 20px;
		height: 20px;
		border-radius: 10px;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0 5px;
		border: 2px solid #ffffff;
		box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
		z-index: 2;
	}

	.pulse-ring {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		border-radius: 50%;
		border: 2px solid var(--theme, #fd3d57);
		animation: pulseRing 1.8s infinite cubic-bezier(0.25, 1, 0.5, 1);
		pointer-events: none;
	}

	/* Chat Window / Drawer */
	.chat-window {
		position: fixed;
		bottom: 92px;
		right: 24px;
		width: 380px;
		max-width: calc(100vw - 32px);
		height: 540px;
		max-height: calc(100dvh - 120px);
		background: #ffffff;
		border-radius: 20px;
		box-shadow: 0 20px 48px -8px rgba(15, 23, 42, 0.22), 0 10px 20px -6px rgba(15, 23, 42, 0.1);
		z-index: 1000;
		display: flex;
		flex-direction: column;
		overflow: hidden;
		animation: chatWindowIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
		border: 1px solid rgba(15, 23, 42, 0.08);
	}

	.chat-window-backdrop {
		display: none;
	}

	@media (max-width: 575.98px) {
		.chat-window {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			width: 100%;
			max-width: 100%;
			height: 100dvh;
			max-height: 100dvh;
			border-radius: 0;
			border: none;
			z-index: 10005;
		}

		.chat-window-backdrop {
			display: block;
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.4);
			backdrop-filter: blur(4px);
			z-index: 10004;
		}
	}

	/* Header */
	.chat-header {
		background: linear-gradient(135deg, var(--theme, #fd3d57) 0%, color-mix(in srgb, var(--theme, #fd3d57) 85%, #000) 100%);
		color: #ffffff;
		padding: 14px 18px;
		display: flex;
		align-items: center;
		justify-content: space-between;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
		flex-shrink: 0;
	}

	.header-info {
		display: flex;
		align-items: center;
		gap: 12px;
	}

	.support-avatar-wrapper {
		position: relative;
	}

	.support-avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.2);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 18px;
		color: #ffffff;
		backdrop-filter: blur(4px);
	}

	.online-dot {
		position: absolute;
		bottom: 1px;
		right: 1px;
		width: 10px;
		height: 10px;
		border-radius: 50%;
		background: #22c55e;
		border: 2px solid #ffffff;
	}

	.support-meta {
		display: flex;
		flex-direction: column;
		gap: 1px;
	}

	.support-title {
		font-size: 15px;
		font-weight: 700;
		color: #ffffff;
		margin: 0;
		line-height: 1.2;
	}

	.support-status {
		font-size: 11px;
		color: rgba(255, 255, 255, 0.85);
		margin: 0;
		display: flex;
		align-items: center;
		gap: 4px;
	}

	.status-indicator {
		display: inline-block;
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #22c55e;
	}

	.header-actions {
		display: flex;
		align-items: center;
		gap: 6px;
	}

	.header-btn {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.15);
		border: none;
		color: #ffffff;
		font-size: 14px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: background 0.2s ease, transform 0.2s ease;
	}

	.header-btn:hover {
		background: rgba(255, 255, 255, 0.3);
		transform: scale(1.05);
	}

	.spin {
		animation: spinAnim 1s linear infinite;
	}

	/* Guest State */
	.guest-chat-card {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 32px 24px;
		text-align: center;
		background: #f8fafc;
	}

	.guest-icon {
		width: 72px;
		height: 72px;
		border-radius: 50%;
		background: color-mix(in srgb, var(--theme, #fd3d57) 12%, #fff);
		color: var(--theme, #fd3d57);
		font-size: 32px;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 18px;
	}

	.guest-title {
		font-size: 18px;
		font-weight: 700;
		color: #0f172a;
		margin: 0 0 8px 0;
	}

	.guest-desc {
		font-size: 13px;
		color: #64748b;
		line-height: 1.5;
		margin: 0 0 24px 0;
		max-width: 280px;
	}

	.guest-actions {
		display: flex;
		flex-direction: column;
		gap: 10px;
		width: 100%;
		max-width: 260px;
	}

	.guest-btn-primary {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		height: 44px;
		border-radius: 12px;
		background: var(--theme, #fd3d57);
		color: #ffffff;
		font-size: 14px;
		font-weight: 700;
		text-decoration: none;
		box-shadow: 0 4px 14px rgba(253, 61, 87, 0.35);
		transition: transform 0.2s ease, opacity 0.2s ease;
	}

	.guest-btn-primary:hover {
		color: #ffffff;
		opacity: 0.94;
		transform: translateY(-1px);
	}

	.guest-btn-secondary {
		display: flex;
		align-items: center;
		justify-content: center;
		height: 42px;
		border-radius: 12px;
		border: 1px solid #cbd5e1;
		background: #ffffff;
		color: #334155;
		font-size: 13px;
		font-weight: 600;
		text-decoration: none;
		transition: background 0.2s ease;
	}

	.guest-btn-secondary:hover {
		background: #f1f5f9;
		color: #0f172a;
	}

	.guest-footer-info {
		margin-top: 28px;
		display: flex;
		align-items: center;
		gap: 6px;
		font-size: 12px;
		color: #94a3b8;
	}

	/* Chat Messages Stream */
	.chat-messages-container {
		flex: 1;
		overflow-y: auto;
		padding: 16px;
		display: flex;
		flex-direction: column;
		gap: 12px;
		background: #f8fafc;
		scroll-behavior: smooth;
	}

	.chat-date-separator {
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 8px 0;
	}

	.chat-date-separator span {
		background: #e2e8f0;
		color: #64748b;
		font-size: 11px;
		font-weight: 600;
		padding: 2px 10px;
		border-radius: 999px;
	}

	.message-row {
		display: flex;
		align-items: flex-end;
		gap: 8px;
		width: 100%;
	}

	.message-row.is-user {
		justify-content: flex-end;
	}

	.sender-avatar {
		width: 28px;
		height: 28px;
		border-radius: 50%;
		background: #e2e8f0;
		color: #475569;
		font-size: 12px;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
		margin-bottom: 2px;
	}

	.bubble-wrapper {
		display: flex;
		flex-direction: column;
		max-width: 80%;
	}

	.message-row.is-user .bubble-wrapper {
		align-items: flex-end;
	}

	.sender-name {
		font-size: 11px;
		color: #94a3b8;
		margin-bottom: 3px;
		margin-left: 4px;
	}

	.message-bubble {
		position: relative;
		padding: 10px 14px;
		border-radius: 16px;
		font-size: 13px;
		line-height: 1.45;
		word-break: break-word;
		box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
	}

	.message-row:not(.is-user) .message-bubble {
		background: #ffffff;
		color: #1e293b;
		border: 1px solid #e2e8f0;
		border-bottom-left-radius: 4px;
	}

	.message-row.is-user .message-bubble {
		background: var(--theme, #fd3d57);
		color: #ffffff;
		border-bottom-right-radius: 4px;
	}

	.bubble-text {
		margin: 0;
		white-space: pre-wrap;
	}

	.bubble-attachments {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
		margin-top: 6px;
	}

	.att-image-link {
		display: block;
		border-radius: 8px;
		overflow: hidden;
		max-width: 180px;
		max-height: 140px;
	}

	.att-image-link img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
		transition: transform 0.2s ease;
	}

	.att-image-link:hover img {
		transform: scale(1.04);
	}

	.bubble-footer {
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 4px;
		margin-top: 4px;
		font-size: 10px;
		opacity: 0.8;
	}

	.message-row.is-user .bubble-footer {
		color: rgba(255, 255, 255, 0.9);
	}

	.message-row:not(.is-user) .bubble-footer {
		color: #94a3b8;
	}

	.msg-status {
		display: inline-flex;
		align-items: center;
		font-size: 10px;
	}

	.text-read {
		color: #93c5fd;
	}

	.msg-delete-btn {
		background: transparent;
		border: none;
		color: rgba(255, 255, 255, 0.7);
		font-size: 10px;
		cursor: pointer;
		padding: 0 2px;
		margin-left: 2px;
		opacity: 0.6;
		transition: opacity 0.2s ease;
	}

	.msg-delete-btn:hover {
		opacity: 1;
		color: #ffffff;
	}

	/* Typing & Empty States */
	.chat-loading-state,
	.chat-empty-state {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 24px;
		text-align: center;
		color: #64748b;
	}

	.chat-spinner {
		width: 32px;
		height: 32px;
		border: 3px solid #e2e8f0;
		border-top-color: var(--theme, #fd3d57);
		border-radius: 50%;
		animation: spinAnim 0.8s linear infinite;
		margin-bottom: 12px;
	}

	.empty-icon {
		width: 56px;
		height: 56px;
		border-radius: 50%;
		background: #ffffff;
		border: 1px solid #e2e8f0;
		color: var(--theme, #fd3d57);
		font-size: 24px;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 12px;
	}

	.chat-empty-state h5 {
		font-size: 15px;
		font-weight: 700;
		color: #1e293b;
		margin-bottom: 6px;
	}

	.chat-empty-state p {
		font-size: 12px;
		color: #64748b;
		max-width: 220px;
		margin: 0;
	}

	.message-bubble.sending {
		display: flex;
		align-items: center;
		gap: 4px;
		padding: 8px 12px;
	}

	.typing-dot {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #ffffff;
		animation: typingBlink 1.4s infinite ease-in-out both;
	}

	.typing-dot:nth-child(2) {
		animation-delay: 0.2s;
	}

	.typing-dot:nth-child(3) {
		animation-delay: 0.4s;
	}

	/* Composer */
	.chat-composer {
		background: #ffffff;
		border-top: 1px solid #e2e8f0;
		padding: 10px 14px;
		display: flex;
		flex-direction: column;
		gap: 8px;
		flex-shrink: 0;
	}

	.composer-preview {
		display: flex;
		align-items: center;
		gap: 8px;
		background: #f1f5f9;
		padding: 6px 10px;
		border-radius: 10px;
		font-size: 12px;
	}

	.composer-preview img {
		width: 36px;
		height: 36px;
		object-fit: cover;
		border-radius: 6px;
	}

	.preview-name {
		flex: 1;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		color: #334155;
	}

	.preview-remove {
		background: transparent;
		border: none;
		color: #ef4444;
		font-size: 14px;
		cursor: pointer;
		padding: 2px 4px;
	}

	.composer-error {
		font-size: 11px;
		color: #ef4444;
		display: flex;
		align-items: center;
		gap: 4px;
	}

	.composer-form {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.composer-btn-attach {
		background: transparent;
		border: none;
		color: #64748b;
		font-size: 18px;
		cursor: pointer;
		padding: 6px;
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: color 0.2s ease, background 0.2s ease;
	}

	.composer-btn-attach:hover {
		color: var(--theme, #fd3d57);
		background: #f1f5f9;
	}

	.composer-input {
		flex: 1;
		border: 1px solid #cbd5e1;
		border-radius: 20px;
		padding: 8px 14px;
		font-size: 13px;
		line-height: 1.4;
		resize: none;
		max-height: 80px;
		outline: none;
		background: #f8fafc;
		color: #0f172a;
		transition: border-color 0.2s ease, background 0.2s ease;
		box-sizing: border-box;
	}

	.composer-input:focus {
		border-color: var(--theme, #fd3d57);
		background: #ffffff;
	}

	.composer-btn-send {
		width: 38px;
		height: 38px;
		border-radius: 50%;
		border: none;
		background: var(--theme, #fd3d57);
		color: #ffffff;
		font-size: 14px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		flex-shrink: 0;
		transition: transform 0.2s ease, opacity 0.2s ease;
	}

	.composer-btn-send:hover:not(:disabled) {
		transform: scale(1.06);
	}

	.composer-btn-send:disabled {
		opacity: 0.45;
		cursor: not-allowed;
	}

	/* Keyframes */
	@keyframes pulseRing {
		0% {
			transform: scale(0.95);
			opacity: 0.8;
		}
		50% {
			transform: scale(1.35);
			opacity: 0;
		}
		100% {
			transform: scale(1.35);
			opacity: 0;
		}
	}

	@keyframes chatWindowIn {
		0% {
			opacity: 0;
			transform: translateY(20px) scale(0.96);
		}
		100% {
			opacity: 1;
			transform: translateY(0) scale(1);
		}
	}

	@keyframes teaserSlideIn {
		0% {
			opacity: 0;
			transform: translateY(10px);
		}
		100% {
			opacity: 1;
			transform: translateY(0);
		}
	}

	@keyframes spinAnim {
		to {
			transform: rotate(360deg);
		}
	}

	@keyframes typingBlink {
		0%, 80%, 100% {
			opacity: 0.3;
			transform: scale(0.8);
		}
		40% {
			opacity: 1;
			transform: scale(1.1);
		}
	}
</style>
