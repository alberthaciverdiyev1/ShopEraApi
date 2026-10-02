<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount, onDestroy } from 'svelte';
	import { user } from '$lib/services/auth';
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

	const POLL_INTERVAL = 10000;

	let text = $state('');
	let attachment = $state<File | null>(null);
	let attachmentPreview = $state<string | null>(null);
	let scrollEl = $state<HTMLDivElement | null>(null);
	let fileInput = $state<HTMLInputElement | null>(null);
	let pollTimer: ReturnType<typeof setInterval> | undefined;

	const currentUserId = $derived($user?.id ?? null);

	function scrollToBottom() {
		if (scrollEl) scrollEl.scrollTop = scrollEl.scrollHeight;
	}

	function formatTime(value: string) {
		const date = new Date(value);
		return Number.isNaN(date.getTime())
			? ''
			: date.toLocaleString('az-AZ', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
	}

	async function refresh({ markRead = true }: { markRead?: boolean } = {}) {
		const messages = await loadChatMessages();
		if (!markRead) return;

		const unreadAdmin = messages.find((m) => m.sender_id !== currentUserId && !m.is_read);
		if (unreadAdmin) await markChatRead(unreadAdmin.conversation_id);
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
		const value = text.trim();
		if (!value && !attachment) return;

		try {
			await sendChatMessage(value, attachment);
			text = '';
			clearAttachment();
			await refresh({ markRead: false });
		} catch {
			// chatError store already carries the message
		}
	}

	async function handleDelete(id: number) {
		try {
			await deleteChatMessage(id);
		} catch {
			// keep the message in place on failure
		}
	}

	function handleKeydown(event: KeyboardEvent) {
		if (event.key === 'Enter' && !event.shiftKey) {
			event.preventDefault();
			handleSend();
		}
	}

	onMount(() => {
		refresh();
		pollTimer = setInterval(() => refresh(), POLL_INTERVAL);
	});

	onDestroy(() => {
		if (pollTimer) clearInterval(pollTimer);
		if (attachmentPreview) URL.revokeObjectURL(attachmentPreview);
	});

	$effect(() => {
		void $chatMessages.length;
		scrollToBottom();
	});
</script>

<div class="chat-panel">
	<div class="chat-head">
		<div>
			<h3 class="panel-title">{$translate('Mesajlar')}</h3>
			<p class="chat-subtitle">{$translate('Dəstək komandası ilə söhbət')}</p>
		</div>
		<button type="button" class="refresh-btn" title={$translate('Yenilə')} onclick={() => refresh()}>
			<i class="fa-solid fa-rotate"></i>
		</button>
	</div>

	<div class="chat-thread" bind:this={scrollEl}>
		{#if $chatLoading && $chatMessages.length === 0}
			<p class="muted">{$translate('Yüklənir…')}</p>
		{:else if $chatMessages.length === 0}
			<p class="muted">{$translate('Hələ mesajınız yoxdur. Sualınızı yazın, dəstək komandası cavablandırsın.')}</p>
		{:else}
			{#each $chatMessages as message (message.id)}
				{@const mine = message.sender_id === currentUserId}
				<div class="bubble-row" class:mine>
					<div class="bubble">
						{#if message.message}
							<p class="bubble-text">{message.message}</p>
						{/if}

						{#if message.attachments?.length}
							<div class="bubble-attachments">
								{#each message.attachments as file (file.id)}
									<a href={file.url} target="_blank" rel="noopener">
										<img src={file.url} alt={$translate('Qoşma')} loading="lazy" />
									</a>
								{/each}
							</div>
						{/if}

						<div class="bubble-meta">
							<span>{formatTime(message.created_at)}</span>
							{#if mine}
								<button type="button" class="delete-btn" title="Sil" onclick={() => handleDelete(message.id)}>
									<i class="fa-solid fa-trash-can"></i>
								</button>
							{/if}
						</div>
					</div>
				</div>
			{/each}
		{/if}
	</div>

	{#if $chatError}
		<p class="chat-error">{$chatError}</p>
	{/if}

	{#if attachmentPreview}
		<div class="attachment-preview">
			<img src={attachmentPreview} alt={$translate('Seçilmiş şəkil')} />
			<button type="button" class="remove-attachment" title={$translate('Ləğv et')} onclick={clearAttachment}>
				<i class="fa-solid fa-xmark"></i>
			</button>
		</div>
	{/if}

	<div class="chat-composer">
		<label class="attach-btn" title={$translate('Şəkil əlavə et')}>
			<i class="fa-solid fa-paperclip"></i>
			<input
				bind:this={fileInput}
				type="file"
				accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
				class="d-none"
				onchange={pickAttachment}
			/>
		</label>

		<textarea
			bind:value={text}
			onkeydown={handleKeydown}
			rows="1"
			placeholder={$translate('Mesajınızı yazın…')}
		></textarea>

		<button
			type="button"
			class="send-btn"
			disabled={$chatSending || (!text.trim() && !attachment)}
			onclick={handleSend}
		>
			{#if $chatSending}
				<i class="fa-solid fa-spinner fa-spin"></i>
			{:else}
				<i class="fa-solid fa-paper-plane"></i>
			{/if}
			<span>{$translate('Göndər')}</span>
		</button>
	</div>
</div>

<style>
	.chat-panel {
		display: flex;
		flex-direction: column;
		border: 1px solid #eef0f4;
		border-radius: 14px;
		background: #fff;
		overflow: hidden;
	}
	.chat-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 16px 18px;
		border-bottom: 1px solid #eef0f4;
	}
	.panel-title { margin: 0; font-size: 18px; }
	.chat-subtitle { margin: 2px 0 0; color: #6b7280; font-size: 13px; }
	.refresh-btn {
		background: none; border: 0; color: #6b7280; cursor: pointer; font-size: 15px;
	}
	.refresh-btn:hover { color: var(--theme); }

	.chat-thread {
		flex: 1;
		min-height: 320px;
		max-height: 480px;
		overflow-y: auto;
		padding: 18px;
		display: flex;
		flex-direction: column;
		gap: 12px;
		background: #fafbfc;
	}
	.muted { color: #6b7280; margin: auto; text-align: center; }

	.bubble-row { display: flex; }
	.bubble-row.mine { justify-content: flex-end; }
	.bubble {
		max-width: 74%;
		padding: 10px 14px;
		border-radius: 14px;
		background: #fff;
		border: 1px solid #eef0f4;
		border-bottom-left-radius: 4px;
		box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
	}
	.bubble-row.mine .bubble {
		background: color-mix(in srgb, var(--theme) 10%, white);
		border-color: color-mix(in srgb, var(--theme) 22%, white);
		border-bottom-left-radius: 14px;
		border-bottom-right-radius: 4px;
	}
	.bubble-text { margin: 0; white-space: pre-wrap; word-break: break-word; font-size: 14px; }
	.bubble-attachments { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
	.bubble-attachments img {
		width: 120px; height: 120px; object-fit: cover; border-radius: 8px; display: block;
	}
	.bubble-meta {
		display: flex; align-items: center; justify-content: flex-end; gap: 8px;
		margin-top: 6px; font-size: 11px; color: #9aa1ad;
	}
	.delete-btn { background: none; border: 0; padding: 0; color: #9aa1ad; cursor: pointer; }
	.delete-btn:hover { color: var(--theme); }

	.chat-error { margin: 0; padding: 8px 18px; color: #b42318; font-size: 13px; }

	.attachment-preview {
		position: relative; margin: 0 18px 8px; width: 72px; height: 72px;
	}
	.attachment-preview img {
		width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 1px solid #eef0f4;
	}
	.remove-attachment {
		position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%;
		border: 0; background: #1f2937; color: #fff; font-size: 11px; cursor: pointer; line-height: 1;
	}

	.chat-composer {
		display: flex; align-items: flex-end; gap: 10px;
		padding: 14px 18px; border-top: 1px solid #eef0f4;
	}
	.attach-btn {
		cursor: pointer; color: #6b7280; font-size: 17px; padding-bottom: 10px;
	}
	.attach-btn:hover { color: var(--theme); }
	.chat-composer textarea {
		flex: 1; resize: none; border: 1px solid #e5e7eb; border-radius: 10px;
		padding: 10px 12px; font-size: 14px; line-height: 1.4; max-height: 120px;
	}
	.chat-composer textarea:focus { outline: none; border-color: var(--theme); }
	.send-btn {
		display: inline-flex; align-items: center; gap: 7px;
		border: 0; border-radius: 10px; padding: 11px 18px; cursor: pointer;
		background: var(--theme); color: #fff; font-size: 14px; font-weight: 500;
	}
	.send-btn:disabled { opacity: 0.55; cursor: not-allowed; }

	@media (max-width: 575px) {
		.bubble { max-width: 88%; }
		.send-btn span { display: none; }
	}
</style>
