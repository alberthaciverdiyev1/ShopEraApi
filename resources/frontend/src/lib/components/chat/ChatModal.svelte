<script lang="ts">
	import { goto } from '$app/navigation';
	import { isLoggedIn } from '$lib/services/auth';
	import { translate } from '$lib/i18n';
	import {
		listChats,
		showChat,
		startChat,
		sendChatMessage,
		refreshChatUnread,
		type ChatConversation,
		type ChatMessage
	} from '$lib/services/listing-chat';

	let { open = $bindable(false), productId = null }: { open?: boolean; productId?: number | null } = $props();

	let conversations = $state<ChatConversation[]>([]);
	let activeId = $state<number | null>(null);
	let messages = $state<ChatMessage[]>([]);
	let loading = $state(false);
	let draft = $state('');
	let sending = $state(false);
	let threadEl = $state<HTMLDivElement | null>(null);
	let showListMobile = $state(true);

	const active = $derived(conversations.find((c) => c.id === activeId) ?? null);

	function scrollBottom() {
		requestAnimationFrame(() => threadEl?.scrollTo({ top: threadEl.scrollHeight, behavior: 'smooth' }));
	}

	async function openThread(id: number) {
		activeId = id;
		showListMobile = false;
		const thread = await showChat(id);
		messages = thread.messages;
		conversations = await listChats();
		await refreshChatUnread();
		scrollBottom();
	}

	async function initialize() {
		if (!$isLoggedIn) return;
		loading = true;
		try {
			conversations = await listChats();

			if (productId) {
				const thread = await startChat(productId);
				activeId = thread.conversation.id;
				messages = thread.messages;
				showListMobile = false;
				conversations = await listChats();
				scrollBottom();
			} else if (conversations.length) {
				await openThread(conversations[0].id);
			}

			await refreshChatUnread();
		} catch {
			/* ignore */
		} finally {
			loading = false;
		}
	}

	async function send() {
		if (!activeId || !draft.trim() || sending) return;
		sending = true;
		try {
			const message = await sendChatMessage(activeId, draft.trim());
			messages = [...messages, message];
			draft = '';
			scrollBottom();
			conversations = await listChats();
		} finally {
			sending = false;
		}
	}

	function close() {
		open = false;
	}

	$effect(() => {
		if (open) initialize();
	});
</script>

{#if open}
	<!-- svelte-ignore a11y_click_events_have_key_events -->
	<div class="chat-overlay" onclick={(e) => e.target === e.currentTarget && close()} role="presentation">
		<div class="chat-panel" role="dialog" aria-modal="true" aria-label={$translate('Messages')}>
			<header class="chat-header">
				<span class="chat-title"><i class="fa-solid fa-comments"></i> {$translate('Messages')}</span>
				<button type="button" class="chat-close" onclick={close} aria-label={$translate('Close')}>×</button>
			</header>

			{#if !$isLoggedIn}
				<div class="chat-login">
					<i class="fa-solid fa-lock"></i>
					<p>{$translate('Sign in to send messages')}</p>
					<button type="button" class="theme-btn" onclick={() => goto('/login')}>{$translate('Login')}</button>
				</div>
			{:else}
				<div class="chat-body">
					<aside class="chat-list" class:mobile-hidden={!showListMobile}>
						{#if conversations.length === 0}
							<p class="chat-empty">{$translate('No conversations yet')}</p>
						{:else}
							{#each conversations as c (c.id)}
								<button type="button" class="chat-item" class:active={c.id === activeId}
								        onclick={() => openThread(c.id)}>
									<span class="chat-avatar">
										{#if c.product?.image}<img src={c.product.image} alt="" />{:else}<i class="fa-solid fa-box"></i>{/if}
									</span>
									<span class="chat-item-main">
										<span class="chat-item-name">{c.other?.name ?? $translate('User')}</span>
										<span class="chat-item-last">{c.last_message ?? c.product?.title ?? ''}</span>
									</span>
									{#if c.unread > 0}<span class="chat-badge">{c.unread}</span>{/if}
								</button>
							{/each}
						{/if}
					</aside>

					<section class="chat-thread" class:mobile-hidden={showListMobile}>
						{#if activeId}
							<button type="button" class="chat-back" onclick={() => (showListMobile = true)}>
								<i class="fa-solid fa-arrow-left"></i> {$translate('Back')}
							</button>
							<div class="chat-messages" bind:this={threadEl}>
								{#each messages as m (m.id)}
									<div class="bubble-row" class:mine={m.mine}>
										<div class="bubble">{m.body}</div>
									</div>
								{/each}
							</div>
							<form class="chat-input" onsubmit={(e) => { e.preventDefault(); send(); }}>
								<input bind:value={draft} placeholder={$translate('Type a message…')} autocomplete="off" />
								<button type="submit" disabled={sending} aria-label={$translate('Send')}>
									<i class="fa-solid fa-paper-plane"></i>
								</button>
							</form>
						{:else}
							<p class="chat-empty">{$translate('Select a conversation')}</p>
						{/if}
					</section>
				</div>
			{/if}
		</div>
	</div>
{/if}

<style>
	.chat-overlay { position: fixed; inset: 0; z-index: 1080; background: rgba(15, 23, 42, 0.55); display: flex; align-items: center; justify-content: center; padding: 16px; }
	.chat-panel { display: flex; flex-direction: column; width: min(920px, 100%); height: min(620px, 90vh); background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.3); }
	.chat-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: #075e54; color: #fff; }
	.chat-title { font-weight: 700; display: inline-flex; align-items: center; gap: 8px; }
	.chat-close { border: 0; background: none; color: #fff; font-size: 26px; line-height: 1; cursor: pointer; }
	.chat-body { display: grid; grid-template-columns: 300px 1fr; flex: 1; min-height: 0; }
	.chat-list { border-right: 1px solid #e6e9f0; overflow-y: auto; background: #f7f9fb; }
	.chat-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 12px 14px; border: 0; border-bottom: 1px solid #eceff4; background: none; text-align: left; cursor: pointer; }
	.chat-item:hover, .chat-item.active { background: #e9f3f1; }
	.chat-avatar { flex-shrink: 0; width: 42px; height: 42px; border-radius: 50%; overflow: hidden; background: #dfe7ee; display: flex; align-items: center; justify-content: center; color: #64748b; }
	.chat-avatar img { width: 100%; height: 100%; object-fit: cover; }
	.chat-item-main { flex: 1; min-width: 0; }
	.chat-item-name { display: block; font-weight: 700; color: #0f172a; font-size: 14px; }
	.chat-item-last { display: block; color: #64748b; font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
	.chat-badge { background: #25d366; color: #fff; font-size: 11px; font-weight: 700; border-radius: 999px; padding: 2px 7px; }
	.chat-thread { display: flex; flex-direction: column; min-width: 0; background: #e5ddd5; }
	.chat-back { display: none; border: 0; background: none; padding: 8px 12px; color: #075e54; font-weight: 700; cursor: pointer; }
	.chat-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px; }
	.bubble-row { display: flex; }
	.bubble-row.mine { justify-content: flex-end; }
	.bubble { max-width: 72%; padding: 8px 12px; border-radius: 12px; background: #fff; color: #0f172a; font-size: 14px; box-shadow: 0 1px 1px rgba(0,0,0,.06); }
	.bubble-row.mine .bubble { background: #d9fdd3; }
	.chat-input { display: flex; gap: 8px; padding: 10px; background: #fff; border-top: 1px solid #e6e9f0; }
	.chat-input input { flex: 1; border: 0; outline: 0; padding: 10px 12px; border-radius: 999px; background: #f0f2f5; }
	.chat-input button { width: 42px; height: 42px; border: 0; border-radius: 50%; background: #25d366; color: #fff; cursor: pointer; }
	.chat-input button:disabled { opacity: .6; }
	.chat-empty { padding: 24px; text-align: center; color: #94a3b8; }
	.chat-login { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: #64748b; }

	@media (max-width: 767.98px) {
		.chat-body { grid-template-columns: 1fr; }
		.mobile-hidden { display: none !important; }
		.chat-back { display: inline-flex; align-items: center; gap: 6px; }
	}
</style>
