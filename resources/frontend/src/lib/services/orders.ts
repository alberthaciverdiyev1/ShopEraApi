import { writable } from 'svelte/store';
import { apiGet, apiPost } from '$lib/utils/api';
import { featureEnabled } from '$lib/services/features';
import type { ApiProduct } from '$lib/services/products';

export interface ApiOrderItem {
	id: number;
	quantity: number;
	unit_price: string | number;
	total_price: string | number;
	product?: ApiProduct | null;
	size?: { id?: number; name?: string } | null;
	color?: { id?: number; name?: string; hex?: string | null } | null;
}

export interface ApiOrder {
	id: number;
	total_price: string | number;
	discount_price?: string | number | null;
	shipping_price?: string | number | null;
	paid_at?: string | null;
	payment_type?: string | null;
	address_type?: string | null;
	note?: string | null;
	created_at?: string;
	transaction_id?: string | null;
	latest_status?: {
		status?: string;
		status_key?: number | null;
		status_key_enum?: string;
		created_at?: string;
	} | null;
	items?: ApiOrderItem[];
}

export const orders = writable<ApiOrder[]>([]);
export const ordersLoading = writable(false);
export const selectedOrder = writable<ApiOrder | null>(null);

export async function loadOrders(): Promise<void> {
	if (!featureEnabled('orders')) {
		orders.set([]);
		return;
	}

	ordersLoading.set(true);
	try {
		const list = await apiGet<ApiOrder[]>('/order');
		orders.set(Array.isArray(list) ? list : []);
	} catch {
		orders.set([]);
	} finally {
		ordersLoading.set(false);
	}
}

/** `GET /order/{id}` also accepts the order's transaction id. */
export async function loadOrder(idOrTransaction: string | number): Promise<ApiOrder | null> {
	if (!featureEnabled('orders')) return null;

	try {
		const order = await apiGet<ApiOrder>(`/order/${idOrTransaction}`);
		selectedOrder.set(order);
		return order;
	} catch {
		return null;
	}
}

/** The receipt endpoint is authenticated, so it is fetched and opened as a blob. */
export async function openReceipt(orderId: number): Promise<void> {
	if (!featureEnabled('order_receipt')) return;

	const { API_BASE, TOKEN_KEY } = await import('$lib/utils/api');
	const token = localStorage.getItem(TOKEN_KEY);

	const response = await fetch(`${API_BASE}/order/receipt/${orderId}`, {
		headers: { Accept: 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}) }
	});

	if (!response.ok) throw new Error(`Receipt unavailable (${response.status})`);

	const payload = await response.json().catch(() => null);
	const url = payload?.data?.url ?? payload?.url ?? payload?.data;

	if (typeof url === 'string' && url.startsWith('http')) {
		window.open(url, '_blank');
	} else if (typeof url === 'string') {
		window.open(url, '_blank');
	} else {
		throw new Error('Receipt link was not returned.');
	}
}

/* ---- Checkout ---- */

export interface OrderPreviewItem {
	id: number;
	title: string;
	quantity: number;
	original_price: number;
	discounted_price: number;
	total: number;
	retail_price?: number;
	wholesale_price?: number;
}

export interface OrderPreview {
	items: OrderPreviewItem[];
	main_amount: number;
	discount_amount: number;
	discounted_total: number;
	delivery: number;
	total: number;
	pricing_type?: string;
	is_wholesale_applied?: boolean;
	retail_total?: number;
	wholesale_total?: number;
	city?: string;
	delivery_info?: {
		price: number;
		free_from: number;
	};
}

export interface CreateOrderPayload {
	address_type: string;
	address_type_id?: number | null;
	payment_type?: string;
	pay_with_balance?: boolean;
	promo_code?: string;
	note?: string;
	[key: string]: unknown;
}

export interface OrderPreviewParams {
	addressId?: number | string | null;
	addressType?: string | null;
	addressTypeId?: number | string | null;
	promoCode?: string;
}

/** Server-side basket calculation (delivery, promo, totals) before placing the order. */
export async function fetchOrderPreview(
	paramsOrAddressId?: number | string | null | OrderPreviewParams,
	maybePromoCode?: string
): Promise<OrderPreview> {
	if (!featureEnabled('orders')) return {} as OrderPreview;

	const query: Record<string, string | number> = {};

	if (paramsOrAddressId && typeof paramsOrAddressId === 'object') {
		if (paramsOrAddressId.addressId) query.address_id = paramsOrAddressId.addressId;
		if (paramsOrAddressId.addressType) query.address_type = paramsOrAddressId.addressType;
		if (paramsOrAddressId.addressTypeId) query.address_type_id = paramsOrAddressId.addressTypeId;
		if (paramsOrAddressId.promoCode) query.promo_code = paramsOrAddressId.promoCode;
	} else {
		if (paramsOrAddressId) query.address_id = paramsOrAddressId;
		if (maybePromoCode) query.promo_code = maybePromoCode;
	}

	const preview = await apiGet<OrderPreview>('/order/preview', query);
	return preview ?? ({} as OrderPreview);
}

export async function createOrderFromBasket(
	payload: CreateOrderPayload
): Promise<{ payment_url?: string; transaction_id?: string }> {
	if (!featureEnabled('orders')) throw new Error('Orders are not available.');

	const result = await apiPost<{ payment_url?: string; transaction_id?: string }>('/order', payload);
	return result ?? {};
}
