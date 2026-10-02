import { writable } from 'svelte/store';
import { apiGet } from '$lib/utils/api';

export interface ApiPickupPoint {
	id: number;
	name: string;
	address: string;
	price: number;
	delivery_time?: string | Record<string, string> | null;
	is_active: boolean;
	created_at?: string;
}

export interface ApiDeliveryCity {
	id: string | number;
	key: string;
	name: string;
}

export interface ApiDeliveryDetail {
	id?: number;
	city_name?: string;
	price?: string | number;
	fast_price?: string | number | null;
	free_from?: string | number;
	delivery_time?: string | null;
	fast_delivery_time?: string | null;
	is_active?: boolean;
}

export interface DeliveryCalculation {
	total_delivery_price: number;
	delivery_time: string;
	free_from: number;
	delivery_info: string;
}

export const pickupPoints = writable<ApiPickupPoint[]>([]);
export const pickupPointsLoading = writable(false);

export const deliveryCities = writable<ApiDeliveryCity[]>([]);
export const deliveryCitiesLoading = writable(false);

/** Fetch active pickup points (stores/branches) */
export async function loadPickupPoints(): Promise<ApiPickupPoint[]> {
	pickupPointsLoading.set(true);
	try {
		const list = await apiGet<ApiPickupPoint[]>('/pickup-point');
		const points = (Array.isArray(list) ? list : []).filter((p) => p.is_active);
		pickupPoints.set(points);
		return points;
	} catch {
		pickupPoints.set([]);
		return [];
	} finally {
		pickupPointsLoading.set(false);
	}
}

/** Fetch active cities for delivery */
export async function loadDeliveryCities(): Promise<ApiDeliveryCity[]> {
	deliveryCitiesLoading.set(true);
	try {
		const list = await apiGet<ApiDeliveryCity[]>('/city');
		const cities = Array.isArray(list) ? list : [];
		deliveryCities.set(cities);
		return cities;
	} catch {
		deliveryCities.set([]);
		return [];
	} finally {
		deliveryCitiesLoading.set(false);
	}
}

/** Fetch delivery rates for a specific city */
export async function loadCityDeliveryDetails(cityName: string): Promise<ApiDeliveryDetail | null> {
	try {
		return await apiGet<ApiDeliveryDetail>('/delivery/details', { name: cityName });
	} catch {
		return null;
	}
}

/** Calculate delivery cost on the server for any addressType + addressTypeId */
export async function calculateDeliveryCost(
	addressType: 'STANDARD' | 'STANDARD_FAST' | 'PICKUP_POINT' | 'TAKE_FROM_STORE',
	addressTypeId?: number | null
): Promise<DeliveryCalculation | null> {
	try {
		const query: Record<string, string | number> = { addressType };
		if (addressTypeId) query.addressTypeId = addressTypeId;
		return await apiGet<DeliveryCalculation>('/order/calculate-delivery-price', query);
	} catch {
		return null;
	}
}

/** Helper to format delivery time from multi-language object or string */
export function getLocalizedDeliveryTime(
	time: string | Record<string, string> | null | undefined,
	locale: string = 'az'
): string {
	if (!time) return '';
	if (typeof time === 'string') return time;
	if (typeof time === 'object') {
		return time[locale] || time['az'] || Object.values(time)[0] || '';
	}
	return '';
}
