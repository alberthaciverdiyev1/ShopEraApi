<?php

namespace Modules\Manager\Database\Seeders;

use Modules\Manager\Entities\Feature;
use Illuminate\Database\Seeder;

/**
 * Feature catalogue derived from what the ShopEra API + Svelte storefront
 * actually expose. Nothing speculative is listed here.
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        // key, name, type(bool|limit), default, group
        $features = [
            // ── Kataloq (Product, Category, Brand, Color, Size, Filter) ──
            ['products', 'Məhsullar', 'bool', '1', 'Kataloq'],
            ['categories', 'Kateqoriyalar', 'bool', '1', 'Kataloq'],
            ['brands', 'Brendlər', 'bool', '1', 'Kataloq'],
            ['colors', 'Rənglər', 'bool', '1', 'Kataloq'],
            ['sizes', 'Ölçülər', 'bool', '1', 'Kataloq'],
            ['product_filters', 'Məhsul filtrləri', 'bool', '1', 'Kataloq'],
            ['product_images', 'Məhsul şəkilləri', 'bool', '1', 'Kataloq'],
            ['product_videos', 'Məhsul videoları', 'bool', '1', 'Kataloq'],
            ['product_story_videos', 'Story videoları', 'bool', '0', 'Kataloq'],
            ['bulk_price_update', 'Toplu qiymət dəyişikliyi', 'bool', '0', 'Kataloq'],
            ['ai_description', 'AI təsvir generasiyası', 'bool', '0', 'Kataloq'],
            ['stock_subscriptions', 'Stok bildiriş abunəliyi', 'bool', '1', 'Kataloq'],

            // ── Kontent (Banner, Popup, Story, Blog, Faq, LegalTerms) ──
            ['banners', 'Bannerlər', 'bool', '1', 'Kontent'],
            ['popups', 'Popuplar', 'bool', '1', 'Kontent'],
            ['stories', 'Story', 'bool', '1', 'Kontent'],
            ['blog', 'Bloq', 'bool', '0', 'Kontent'],
            ['faq', 'FAQ', 'bool', '1', 'Kontent'],
            ['legal_terms', 'Hüquqi şərtlər', 'bool', '1', 'Kontent'],
            ['contact_page', 'Əlaqə səhifəsi', 'bool', '1', 'Kontent'],
            ['sitemap_seo', 'Sitemap & SEO', 'bool', '1', 'Kontent'],
            ['theme_colors', 'Tema rəngləri', 'bool', '0', 'Kontent'],
            ['custom_theme', 'Öz custom teması', 'bool', '0', 'Kontent'],

            // ── Sifariş & Ödəniş (Order, Payment, Balance) ──
            ['orders', 'Sifarişlər', 'bool', '1', 'Sifariş & Ödəniş'],
            ['order_receipt', 'Qəbz / faktura', 'bool', '1', 'Sifariş & Ödəniş'],
            ['online_payment', 'Onlayn ödəniş (Epoint)', 'bool', '1', 'Sifariş & Ödəniş'],
            ['cash_on_delivery', 'Qapıda nağd ödəniş', 'bool', '1', 'Sifariş & Ödəniş'],
            ['whatsapp_orders', 'WhatsApp ilə sifariş', 'bool', '1', 'Sifariş & Ödəniş'],
            ['phone_orders', 'Telefon ilə sifariş', 'bool', '0', 'Sifariş & Ödəniş'],
            ['instagram_orders', 'Instagram / DM ilə sifariş', 'bool', '0', 'Sifariş & Ödəniş'],
            ['saved_cards', 'Yadda saxlanan kartlar', 'bool', '0', 'Sifariş & Ödəniş'],
            ['balance_wallet', 'Balans / cüzdan', 'bool', '1', 'Sifariş & Ödəniş'],
            ['refunds', 'Geri qaytarma (balansa)', 'bool', '0', 'Sifariş & Ödəniş'],

            // ── Çatdırılma (Delivery) ──
            ['delivery_prices', 'Çatdırılma qiymətləri', 'bool', '1', 'Çatdırılma'],
            ['delivery_cities', 'Şəhərlər', 'bool', '1', 'Çatdırılma'],
            ['delivery_info', 'Çatdırılma məlumatları', 'bool', '1', 'Çatdırılma'],
            ['pickup_points', 'Gəl-al nöqtələri', 'bool', '1', 'Çatdırılma'],
            ['fast_delivery', 'Tez çatdırılma', 'bool', '0', 'Çatdırılma'],

            // ── Marketinq & Əlaqə (PromoCode, Referral, Review, Chat, Notification) ──
            ['promo_codes', 'Promo kodlar', 'bool', '1', 'Marketinq & Əlaqə'],
            ['referrals', 'Referal sistemi', 'bool', '0', 'Marketinq & Əlaqə'],
            ['reviews', 'Şərhlər & reytinq', 'bool', '1', 'Marketinq & Əlaqə'],
            ['chat', 'Canlı dəstək (chat)', 'bool', '0', 'Marketinq & Əlaqə'],
            ['auto_reply', 'Avtomatik cavablar', 'bool', '0', 'Marketinq & Əlaqə'],
            ['promo_blocks', 'Promo bloklar (offer/reklam)', 'bool', '0', 'Marketinq & Əlaqə'],
            ['push_notifications', 'Push bildiriş (FCM)', 'bool', '0', 'Marketinq & Əlaqə'],
            ['sms', 'SMS (OTP)', 'bool', '1', 'Marketinq & Əlaqə'],

            // ── Marketplace (Setting: store/marketplace) ──
            ['marketplace', 'Marketplace (çox satıcı)', 'bool', '0', 'Marketplace'],
            ['store_commission', 'Komissiya idarəsi', 'bool', '0', 'Marketplace'],
            ['store_settings', 'Mağaza parametrləri', 'bool', '0', 'Marketplace'],

            // ── İstifadəçi (User) ──
            ['users', 'İstifadəçilər', 'bool', '1', 'İstifadəçi'],
            ['roles_permissions', 'Rol & icazə idarəsi', 'bool', '1', 'İstifadəçi'],
            ['addresses', 'Ünvan kitabçası', 'bool', '1', 'İstifadəçi'],
            ['favorites', 'İstək siyahısı', 'bool', '1', 'İstifadəçi'],
            ['basket', 'Səbət', 'bool', '1', 'İstifadəçi'],

            // ── Sistem ──
            ['multi_language', 'Çoxdillilik (az/en/ru/tr)', 'bool', '1', 'Sistem'],
            ['statistics', 'Statistika & hesabat', 'bool', '0', 'Sistem'],
            ['settings', 'Parametrlər', 'bool', '1', 'Sistem'],
            ['custom_domain', 'Öz domeni', 'bool', '0', 'Sistem'],

            // ── Limitlər ──
            ['max_products', 'Maks. məhsul', 'limit', '100', 'Limitlər'],
            ['max_categories', 'Maks. kateqoriya', 'limit', '20', 'Limitlər'],
            ['max_staff', 'Maks. işçi', 'limit', '2', 'Limitlər'],
            ['max_orders', 'Aylıq maks. sifariş', 'limit', '500', 'Limitlər'],
            ['storage_gb', 'Yaddaş (GB)', 'limit', '5', 'Limitlər'],

            // ── Plan fərqləndiriciləri (Free / Premium / Business) ──
            ['site_orders', 'Sayt üzərindən sifariş', 'bool', '0', 'Sifariş & Ödəniş'],
            ['shipping_settings', 'Karqo ayarları', 'bool', '0', 'Çatdırılma'],
            ['subdomain', 'Snaker subdomain', 'bool', '1', 'Kontent'],
            ['theme_selection', 'Hazır tema seçimi', 'bool', '1', 'Kontent'],
            ['dark_light_theme', 'Dark / Light tema', 'bool', '0', 'Kontent'],
            ['google_analytics', 'Google Analytics / Pixel', 'bool', '0', 'Marketinq & Əlaqə'],
            ['custom_code', 'Özəl kod / inteqrasiya', 'bool', '0', 'Sistem'],
            ['api_access', 'API girişi', 'bool', '0', 'Sistem'],
            ['product_variants', 'Variantlar (rəng, ölçü)', 'limit', '1', 'Kataloq'],
            ['stock_tracking', 'Stok izləmə', 'limit', '1', 'Kataloq'],
            ['color_customization', 'Rəng fərdiləşdirmə', 'limit', '0', 'Kontent'],
            ['font_customization', 'Font fərdiləşdirmə', 'limit', '0', 'Kontent'],
            ['component_selection', 'Hazır komponent seçimi', 'limit', '0', 'Kontent'],
            ['homepage_editing', 'Ana səhifə redaktəsi', 'limit', '0', 'Kontent'],
            ['branding', 'Snaker brendinqi', 'limit', '0', 'Kontent'],
            ['seo', 'SEO ayarları', 'limit', '1', 'Marketinq & Əlaqə'],
            ['sales_reports', 'Satış hesabatları', 'limit', '0', 'Sistem'],
            ['support_level', 'Dəstək səviyyəsi', 'limit', '0', 'Sistem'],
        ];

        $keys = array_column($features, 0);

        // Remove anything that is not part of the real capability set.
        Feature::query()->whereNotIn('key', $keys)->delete();

        foreach ($features as $i => [$key, $name, $type, $default, $group]) {
            Feature::query()->updateOrCreate(
                ['key' => $key],
                ['name' => $name, 'type' => $type, 'default_value' => $default, 'group' => $group, 'sort_order' => $i]
            );
        }
    }
}
