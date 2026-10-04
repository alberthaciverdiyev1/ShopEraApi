<?php

namespace Modules\Manager\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Manager\Entities\Feature;

/**
 * Feature catalogue, kept in sync with the production control database. Every
 * key here is expected to be wired into the app: the admin sidebar
 * (App\Support\AdminMenu), the EnforceAdminMenuAccess route gate and/or the
 * `feature:` API middleware. Nothing speculative is listed.
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        // key, name, type(bool|limit), default, group
        $features = [
            // Kataloq
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

            // Kontent
            ['banners', 'Bannerlər', 'bool', '1', 'Kontent'],
            ['popups', 'Popuplar', 'bool', '1', 'Kontent'],
            ['stories', 'Story', 'bool', '1', 'Kontent'],
            ['blog', 'Bloq', 'bool', '0', 'Kontent'],
            ['faq', 'FAQ', 'bool', '1', 'Kontent'],
            ['legal_terms', 'Hüquqi şərtlər', 'bool', '1', 'Kontent'],
            ['contact_page', 'Əlaqə səhifəsi', 'bool', '1', 'Kontent'],
            ['theme_colors', 'Tema rəngləri', 'bool', '0', 'Kontent'],
            ['custom_theme', 'Öz custom teması', 'bool', '0', 'Kontent'],

            // Sifariş & Ödəniş
            ['orders', 'Sifarişlər', 'bool', '1', 'Sifariş & Ödəniş'],
            ['order_receipt', 'Qəbz / faktura', 'bool', '1', 'Sifariş & Ödəniş'],
            ['online_payment', 'Onlayn ödəniş (Epoint)', 'bool', '1', 'Sifariş & Ödəniş'],
            ['cash_on_delivery', 'Qapıda nağd ödəniş', 'bool', '1', 'Sifariş & Ödəniş'],
            ['whatsapp_orders', 'WhatsApp ilə sifariş', 'bool', '1', 'Sifariş & Ödəniş'],
            ['balance_wallet', 'Balans / cüzdan', 'bool', '1', 'Sifariş & Ödəniş'],

            // Çatdırılma
            ['delivery_prices', 'Çatdırılma qiymətləri', 'bool', '1', 'Çatdırılma'],
            ['delivery_cities', 'Şəhərlər', 'bool', '1', 'Çatdırılma'],
            ['delivery_info', 'Çatdırılma məlumatları', 'bool', '1', 'Çatdırılma'],
            ['pickup_points', 'Gəl-al nöqtələri', 'bool', '1', 'Çatdırılma'],
            ['fast_delivery', 'Tez çatdırılma', 'bool', '0', 'Çatdırılma'],

            // Marketinq & Əlaqə
            ['promo_codes', 'Promo kodlar', 'bool', '1', 'Marketinq & Əlaqə'],
            ['referrals', 'Referal sistemi', 'bool', '0', 'Marketinq & Əlaqə'],
            ['reviews', 'Şərhlər & reytinq', 'bool', '1', 'Marketinq & Əlaqə'],
            ['chat', 'Canlı dəstək (chat)', 'bool', '0', 'Marketinq & Əlaqə'],
            ['auto_reply', 'Avtomatik cavablar', 'bool', '0', 'Marketinq & Əlaqə'],
            ['show_ads', 'Reklamları göstər (offer/reklam blokları)', 'bool', '1', 'Marketinq & Əlaqə'],
            ['push_notifications', 'Push bildiriş (FCM)', 'bool', '0', 'Marketinq & Əlaqə'],
            ['sms', 'SMS (OTP)', 'bool', '1', 'Marketinq & Əlaqə'],

            // İstifadəçi
            ['users', 'İstifadəçilər', 'bool', '1', 'İstifadəçi'],
            ['roles_permissions', 'Rol & icazə idarəsi', 'bool', '1', 'İstifadəçi'],
            ['addresses', 'Ünvan kitabçası', 'bool', '1', 'İstifadəçi'],
            ['favorites', 'İstək siyahısı', 'bool', '1', 'İstifadəçi'],
            ['basket', 'Səbət', 'bool', '1', 'İstifadəçi'],

            // Sistem
            ['multi_language', 'Çoxdillilik (az/en/ru/tr)', 'bool', '1', 'Sistem'],
            ['statistics', 'Statistika & hesabat', 'bool', '0', 'Sistem'],
            ['settings', 'Parametrlər', 'bool', '1', 'Sistem'],

            // Limitlər
            ['max_products', 'Maks. məhsul', 'limit', '100', 'Limitlər'],
            ['storage_mb', 'Yaddaş (MB)', 'limit', '100', 'Limitlər'],
        ];

        foreach ($features as $i => [$key, $name, $type, $default, $group]) {
            Feature::query()->updateOrCreate(
                ['key' => $key],
                ['name' => $name, 'type' => $type, 'default_value' => $default, 'group' => $group, 'sort_order' => $i]
            );
        }
    }
}
