import { writable } from 'svelte/store';
import { apiGet, apiPost, apiDelete, apiPostForm } from '$lib/utils/api';
import { featureEnabled } from '$lib/services/features';

export interface ApiChatAttachment {
	id: number;
	url: string;
}

export interface ApiChatMessage {
	id: number;
	conversation_id: number;
	sender_type: 'user' | 'admin';
	sender_id: number;
	message: string | null;
	is_read: boolean;
	created_at: string;
	attachments: ApiChatAttachment[];
}

export const chatMessages = writable<ApiChatMessage[]>([]);
export const chatLoading = writable(false);
export const chatSending = writable(false);
export const chatError = writable<string | null>(null);

/** `GET /chat` — the current user's conversation with support. */
export async function loadChatMessages(): Promise<ApiChatMessage[]> {
	if (!featureEnabled('chat')) {
		chatMessages.set([]);
		return [];
	}

	chatLoading.set(true);
	chatError.set(null);
	try {
		const list = await apiGet<ApiChatMessage[]>('/chat');
		// The API returns newest-first; the chat UI needs chronological order.
		const messages = (Array.isArray(list) ? list : []).sort((a, b) => a.id - b.id);
		chatMessages.set(messages);
		return messages;
	} catch (e) {
		chatError.set(e instanceof Error ? e.message : 'Mesajlar yüklənə bilmədi.');
		chatMessages.set([]);
		return [];
	} finally {
		chatLoading.set(false);
	}
}

/** `POST /chat/send` — text and/or an optional image attachment. */
export async function sendChatMessage(message: string, image?: File | null): Promise<void> {
	if (!featureEnabled('chat')) return;

	chatSending.set(true);
	chatError.set(null);
	try {
		if (image) {
			const form = new FormData();
			if (message) form.append('message', message);
			form.append('image', image);
			await apiPostForm('/chat/send', form);
		} else {
			await apiPost('/chat/send', { message });
		}
	} catch (e) {
		chatError.set(e instanceof Error ? e.message : 'Mesaj göndərilə bilmədi.');
		throw e;
	} finally {
		chatSending.set(false);
	}
}

/** `POST /chat/conversation/read/{id}` — marks the other side's messages as read. */
export async function markChatRead(conversationId: number): Promise<void> {
	if (!featureEnabled('chat')) return;

	try {
		await apiPost(`/chat/conversation/read/${conversationId}`);
	} catch {
		// marking as read is best-effort
	}
}

/** `DELETE /chat/message/{id}` */
export async function deleteChatMessage(messageId: number): Promise<void> {
	if (!featureEnabled('chat')) return;

	await apiDelete(`/chat/message/${messageId}`);
	chatMessages.update((list) => list.filter((message) => message.id !== messageId));
}
