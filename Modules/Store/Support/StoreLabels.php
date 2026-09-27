<?php

namespace Modules\Store\Support;

/**
 * Human-readable, localized labels for the raw enum-ish strings the store tables
 * store. The raw values stay in the API untouched — labels are added alongside
 * them, so existing clients that switch on the raw value keep working.
 */
class StoreLabels
{
    public static function storeStatus(?string $value): ?string
    {
        return match ($value) {
            'pending' => __('Admin təsdiqi gözlənilir'),
            'changes_requested' => __('Düzəliş tələb olunur'),
            'approved' => __('Təsdiqlənib'),
            'rejected' => __('İmtina edilib'),
            'suspended' => __('Dayandırılıb'),
            default => $value,
        };
    }

    public static function productApproval(?string $value): ?string
    {
        return match ($value) {
            'draft' => __('Qaralama'),
            'pending' => __('Təsdiq gözlənilir'),
            'approved' => __('Təsdiqlənib'),
            'rejected' => __('İmtina edilib'),
            'unpublished' => __('Satışdan çıxarılıb'),
            default => $value,
        };
    }

    public static function fulfillmentStatus(?string $value): ?string
    {
        return match ($value) {
            'awaiting' => __('Təhvil gözlənilir'),
            'overdue' => __('Gecikib'),
            'penalized' => __('Cərimə tətbiq edilib'),
            'handed_over' => __('Teymur Store-a təhvil verilib'),
            'cancelled' => __('Ləğv edilib'),
            default => __('Naməlum'),
        };
    }

    public static function settlementStatus(?string $value): ?string
    {
        return match ($value) {
            'pending' => __('Gözləyir'),
            'settled' => __('Hesablanıb, çıxarıla bilmir'),
            'released' => __('Balansa köçürülüb'),
            'reversed' => __('Geri qaytarılıb'),
            // Written by the pending-payment expiry job. The seller never sees
            // it (scopeVisibleToSeller excludes it); this is for the admin list.
            'expired' => __('Ödənilmədi, ləğv olundu'),
            default => $value,
        };
    }

    public static function walletType(?string $value): ?string
    {
        return match ($value) {
            'cash_sale_commission' => __('Nağd satış komissiyası'),
            'non_cash_sale' => __('Kart/balans satışı gəliri'),
            'card_top_up' => __('Kartla balans artırma'),
            'top_up' => __('Admin balans artırması'),
            'withdrawal' => __('Balansdan çıxarış'),
            'adjustment' => __('Balans düzəlişi'),
            'late_handover_penalty' => __('Gecikmə cəriməsi'),
            'order_reversal' => __('Ləğv edilmiş sifariş düzəlişi'),
            'order_refund' => __('Qismən geri qaytarma'),
            'withdrawal_paid' => __('Ödənilmiş çıxarış'),
            default => __('Balans əməliyyatı'),
        };
    }

    public static function paymentType(?string $value): ?string
    {
        return match (strtoupper((string) $value)) {
            'CASH' => __('Nağd'),
            'CARD' => __('Kart'),
            'BALANCE' => __('Tətbiq balansı'),
            default => $value,
        };
    }

    public static function withdrawalStatus(?string $value): ?string
    {
        return match ($value) {
            'pending' => __('Gözləmədə'),
            'approved' => __('Təsdiqlənib, ödəniş gözlənilir'),
            'paid' => __('Ödənilib'),
            'rejected' => __('Rədd edilib'),
            'cancelled' => __('Ləğv edilib'),
            default => $value,
        };
    }

    public static function subOrderStatus(?string $value): ?string
    {
        return match ($value) {
            'placed' => __('Sifariş verildi'),
            'handed_over' => __('Təhvil verildi'),
            'cancelled' => __('Ləğv edildi'),
            'returned' => __('Geri qaytarıldı'),
            'partially_returned' => __('Qismən geri qaytarıldı'),
            default => $value,
        };
    }

    public static function deactivatedReason(?string $value): ?string
    {
        return match ($value) {
            'negative_balance_limit' => __('Mənfi balans limiti aşılıb'),
            'admin_status' => __('Admin tərəfindən dayandırılıb'),
            default => null,
        };
    }
}
