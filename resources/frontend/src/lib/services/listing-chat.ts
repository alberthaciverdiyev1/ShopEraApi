import { writable } from 'svelte/store';
import { apiGet, apiPost } from '$lib/utils/api';

export interface ChatProduct {
	id: number;
	title: string;
	image?: string | null;
}
export interface ChatOther {
	id: number;
	name: string;
}
export interface ChatConversation {
	id: number;
	product: ChatProduct | null;
	other: ChatOther | null;
	last_message?: string | null;
	last_message_at?: string | null;
	unread: number;
}
export interface ChatMessage {
	id: number;
	body: string;
	mine: boolean;
	is_read: boolean;
	created_at?: string | null;
}
export interface ChatThread {
	conversation: ChatConversation;
	messages: ChatMessage[];
}

/** Navbar badge: number of unread messages across conversations. */
export const chatUnread = writable(0);

/** Start (or resume) the conversation for a listing. */
export async function startChat(productId: number): Promise<ChatThread> {
	return apiPost<ChatThread>(`/listings/${productId}/chat`);
}

export async function listChats(): Promise<ChatConversation[]> {
	return apiGet<ChatConversation[]>('/listings/chats');
}

export async function showChat(id: number): Promise<ChatThread> {
	return apiGet<ChatThread>(`/listings/chats/${id}`);
}

export async function sendChatMessage(id: number, body: string): Promise<ChatMessage> {
	return apiPost<ChatMessage>(`/listings/chats/${id}/messages`, { body });
}

export async function refreshChatUnread(): Promise<number> {
	try {
		const data = await apiGet<{ count: number }>('/listings/chats/unread');
		const count = data?.count ?? 0;
		chatUnread.set(count);
		return count;
	} catch {
		return 0;
	}
}
