<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { loadOrders, orders, ordersLoading, type ApiOrder } from '$lib/services/orders';

	let { onSelect }: { onSelect?: (order: ApiOrder) => void } = $props();

	onMount(() => loadOrders());

	const money = (value: unknown) => `$${Number(value ?? 0).toFixed(2)}`;
	const date = (value?: string) => (value ? new Date(value).toLocaleDateString('az-AZ') : '—');
</script>

<div class="orders-panel">
    <h3 class="panel-title">{$translate('Sifariş tarixçəsi')}</h3>

    {#if $ordersLoading}
        <p class="muted">{$translate('Yüklənir…')}</p>
    {:else if $orders.length === 0}
        <p class="muted">{$translate('Hələ sifarişiniz yoxdur.')}</p>
    {:else}
        <div class="orders-table-wrap">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tarix</th>
                        <th>Status</th>
                        <th>{$translate('Məhsul')}</th>
                        <th>{$translate('Cəm')}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {#each $orders as order (order.id)}
                        <tr>
                            <td>#{order.id}</td>
                            <td>{date(order.created_at)}</td>
                            <td><span class="status-pill">{order.latest_status?.status ?? '—'}</span></td>
                            <td>{order.items?.length ?? 0}</td>
                            <td>{money(order.total_price)}</td>
                            <td>
                                <button type="button" class="link-btn" onclick={() => onSelect?.(order)}>
                                    Detallar
                                </button>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {/if}
</div>

<style>
	.panel-title { margin-bottom: 16px; font-size: 18px; }
	.muted { color: #6b7280; }
	.orders-table-wrap { overflow-x: auto; }
	.orders-table { width: 100%; border-collapse: collapse; }
	.orders-table th, .orders-table td {
		text-align: start; padding: 12px 14px; border-bottom: 1px solid #eef0f4; font-size: 14px;
	}
	.orders-table th { color: #6b7280; font-weight: 600; }
	.status-pill {
		display: inline-block; padding: 3px 10px; border-radius: 999px;
		background: color-mix(in srgb, var(--theme) 12%, white); color: var(--theme); font-size: 12px;
	}
	.link-btn { background: none; border: 0; padding: 0; color: var(--theme); cursor: pointer; font-size: 14px; }
</style>
