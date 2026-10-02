<script lang="ts">
	import { translate } from '$lib/i18n';
	import { openReceipt, selectedOrder, type ApiOrder } from '$lib/services/orders';
	import { productImage, productTitle } from '$lib/services/products';

	let { order = null, onBack }: { order?: ApiOrder | null; onBack?: () => void } = $props();

	const money = (value: unknown) => `$${Number(value ?? 0).toFixed(2)}`;
	let receiptError = $state<string | null>(null);

	async function receipt() {
		receiptError = null;
		try {
			const current = order ?? $selectedOrder;
			if (current) await openReceipt(current.id);
		} catch (e) {
			receiptError = e instanceof Error ? e.message : 'Qəbz açıla bilmədi';
		}
	}
</script>

<div class="order-detail">
    {#if !order}
        <p class="muted">{$translate('Detalları görmək üçün sifariş tarixçəsindən bir sifariş seçin.')}</p>
    {:else}
        <div class="detail-head">
            <h3>Sifariş #{order.id}</h3>
            <button type="button" class="link-btn" onclick={() => onBack?.()}>{$translate('← Geri')}</button>
        </div>

        <div class="detail-meta">
            <span>Status: <strong>{order.latest_status?.status ?? '—'}</strong></span>
            <span>{$translate('Ödəniş:')} <strong>{order.payment_type ?? '—'}</strong></span>
            <span>{$translate('Ünvan növü:')} <strong>{order.address_type ?? '—'}</strong></span>
        </div>

        <table class="items-table">
            <thead>
                <tr><th>{$translate('Məhsul')}</th><th>{$translate('Ədəd')}</th><th>{$translate('Vahid qiymət')}</th><th>{$translate('Cəm')}</th></tr>
            </thead>
            <tbody>
                {#each order.items ?? [] as item (item.id)}
                    <tr>
                        <td class="item-cell">
                            {#if item.product}
                                <img src={productImage(item.product)} alt={productTitle(item.product)}>
                                <span>{productTitle(item.product)}</span>
                            {:else}
                                <span>{$translate('Məhsul')}</span>
                            {/if}
                        </td>
                        <td>{item.quantity}</td>
                        <td>{money(item.unit_price)}</td>
                        <td>{money(item.total_price)}</td>
                    </tr>
                {/each}
            </tbody>
        </table>

        <div class="totals">
            <div><span>{$translate('Ara cəm')}</span><span>{money(Number(order.total_price) + Number(order.discount_price ?? 0))}</span></div>
            {#if Number(order.discount_price ?? 0) > 0}
                <div class="discount"><span>Endirim</span><span>-{money(order.discount_price)}</span></div>
            {/if}
            <div><span>{$translate('Çatdırılma')}</span><span>{money(order.shipping_price)}</span></div>
            <div class="grand"><span>{$translate('Ümumi')}</span><span>{money(order.total_price)}</span></div>
        </div>

        <button type="button" class="receipt-btn" onclick={receipt}>{$translate('Qəbzi aç')}</button>
        {#if receiptError}<p class="err">{receiptError}</p>{/if}
    {/if}
</div>

<style>
	.muted { color: #6b7280; }
	.detail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
	.detail-head h3 { margin: 0; font-size: 18px; }
	.link-btn { background: none; border: 0; padding: 0; color: var(--theme); cursor: pointer; }
	.detail-meta { display: flex; gap: 22px; flex-wrap: wrap; margin-bottom: 16px; color: #4b5563; font-size: 14px; }
	.items-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
	.items-table th, .items-table td { text-align: start; padding: 10px 12px; border-bottom: 1px solid #eef0f4; font-size: 14px; }
	.item-cell { display: flex; align-items: center; gap: 10px; }
	.item-cell img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; }
	.totals { max-width: 340px; margin-inline-start: auto; }
	.totals > div { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f3f6; font-size: 14px; }
	.totals .grand { font-weight: 700; border-bottom: 0; }
	.totals .discount { color: var(--theme); }
	.receipt-btn {
		margin-top: 16px; background: var(--theme); color: #fff; border: 0;
		border-radius: 10px; padding: 11px 20px; cursor: pointer;
	}
	.err { color: #e2453c; font-size: 14px; margin-top: 8px; }
</style>
