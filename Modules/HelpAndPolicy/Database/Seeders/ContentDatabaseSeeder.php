<?php

namespace Modules\HelpAndPolicy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpAndPolicy\Entities\Faq;
use Modules\HelpAndPolicy\Entities\LegalTerm;

class ContentDatabaseSeeder extends Seeder
{
    /**
     * Storefront content: the legal pages and the FAQ list.
     */
    public function run(): void
    {
        $legal = [
            [
                'type' => 'register_page',
                'html' => [
                    'az' => '<h1>İstifadə Şərtləri</h1><p>Saytdan istifadə etməklə bu şərtləri qəbul etmiş olursunuz.</p><h3>Sifarişlər</h3><p>Sifarişlər təsdiqləndikdən sonra hazırlanır və göstərilən ünvana çatdırılır.</p><h3>Ödəniş</h3><p>Ödəniş kart və ya nağd yolla həyata keçirilə bilər.</p>',
                    'en' => '<h1>Terms of Service</h1><p>By using this site you accept these terms.</p><h3>Orders</h3><p>Orders are prepared after confirmation and delivered to the given address.</p><h3>Payment</h3><p>Payment can be made by card or in cash.</p>',
                    'ru' => '<h1>Условия обслуживания</h1><p>Используя сайт, вы принимаете эти условия.</p><h3>Заказы</h3><p>Заказы готовятся после подтверждения и доставляются по указанному адресу.</p><h3>Оплата</h3><p>Оплата возможна картой или наличными.</p>',
                    'tr' => '<h1>Hizmet Şartları</h1><p>Bu siteyi kullanarak bu şartları kabul etmiş olursunuz.</p><h3>Siparişler</h3><p>Siparişler onaylandıktan sonra hazırlanır ve belirtilen adrese teslim edilir.</p><h3>Ödeme</h3><p>Ödeme kart veya nakit olarak yapılabilir.</p>',
                ],
            ],
            [
                'type' => 'main_page',
                'html' => [
                    'az' => '<h1>Məxfilik Siyasəti</h1><p>Şəxsi məlumatlarınız qanunvericiliyə uyğun qorunur.</p><h3>Toplanan məlumatlar</h3><p>Ad, əlaqə məlumatları və ünvan sifarişin çatdırılması üçün istifadə olunur.</p><h3>Üçüncü tərəflər</h3><p>Məlumatlar yalnız çatdırılma və ödəniş xidmətləri ilə paylaşılır.</p>',
                    'en' => '<h1>Privacy Policy</h1><p>Your personal data is protected in line with the applicable legislation.</p><h3>What we collect</h3><p>Your name, contact details and address are used to deliver the order.</p><h3>Third parties</h3><p>Data is shared only with delivery and payment providers.</p>',
                    'ru' => '<h1>Политика конфиденциальности</h1><p>Ваши персональные данные защищены в соответствии с законодательством.</p><h3>Какие данные мы собираем</h3><p>Имя, контактные данные и адрес используются для доставки заказа.</p><h3>Третьи стороны</h3><p>Данные передаются только службам доставки и оплаты.</p>',
                    'tr' => '<h1>Gizlilik Politikası</h1><p>Kişisel verileriniz mevzuata uygun şekilde korunur.</p><h3>Topladığımız veriler</h3><p>Ad, iletişim bilgileri ve adres siparişin teslimi için kullanılır.</p><h3>Üçüncü taraflar</h3><p>Veriler yalnızca teslimat ve ödeme hizmetleriyle paylaşılır.</p>',
                ],
            ],
            [
                'type' => 'about',
                'html' => [
                    'az' => '<h1>Haqqımızda</h1><p>Snaker — gündəlik ehtiyaclar, ev, mətbəx və geyim kateqoriyalarında seçilmiş məhsulları bir araya gətirən onlayn mağazadır.</p><h3>Missiyamız</h3><p>Keyfiyyətli məhsulları sərfəli qiymətə və sürətli çatdırılma ilə təqdim etmək.</p><h3>Nə üçün biz?</h3><p>Geniş çeşid, etibarlı xidmət və müştəri məmnuniyyəti.</p>',
                    'en' => '<h1>About Us</h1><p>Snaker is an online store bringing together selected products across daily needs, home, kitchen and fashion.</p><h3>Our mission</h3><p>To offer quality products at fair prices with fast delivery.</p><h3>Why us</h3><p>Wide selection, reliable service and customer satisfaction.</p>',
                    'ru' => '<h1>О нас</h1><p>Snaker — интернет-магазин, объединяющий отборные товары для повседневных нужд, дома, кухни и моды.</p><h3>Наша миссия</h3><p>Предлагать качественные товары по доступным ценам с быстрой доставкой.</p><h3>Почему мы</h3><p>Широкий ассортимент, надёжный сервис и довольные клиенты.</p>',
                    'tr' => '<h1>Hakkımızda</h1><p>Snaker; günlük ihtiyaçlar, ev, mutfak ve giyim kategorilerinde seçkin ürünleri bir araya getiren online mağazadır.</p><h3>Misyonumuz</h3><p>Kaliteli ürünleri uygun fiyatla ve hızlı teslimatla sunmak.</p><h3>Neden biz</h3><p>Geniş ürün yelpazesi, güvenilir hizmet ve müşteri memnuniyeti.</p>',
                ],
            ],
        ];

        foreach ($legal as $term) {
            LegalTerm::updateOrCreate(['type' => $term['type']], ['html' => $term['html']]);
        }

        $faqs = [
            ['general', 'Sifarişi necə verə bilərəm?', 'Bəyəndiyiniz məhsulu səbətə əlavə edin və "Səbət" səhifəsindən sifarişi tamamlayın.'],
            ['general', 'Çatdırılma nə qədər vaxt alır?', 'Bakı daxilində 1-2 iş günü, digər şəhərlərə 2-5 iş günü ərzində çatdırılır.'],
            ['billing', 'Ödənişi hansı üsullarla edə bilərəm?', 'Kart ilə onlayn və ya çatdırılma zamanı nağd şəkildə ödəniş edə bilərsiniz.'],
            ['billing', 'Promo kod necə istifadə olunur?', 'Səbət səhifəsində promo kod sahəsinə kodu yazıb tətbiq edin — endirim avtomatik hesablanır.'],
            ['account', 'Şifrəmi necə dəyişə bilərəm?', 'Hesabınıza daxil olduqdan sonra Dashboard → Settings bölməsindən profil məlumatlarınızı yeniləyə bilərsiniz.'],
            ['account', 'Şifrəmi unutmuşam, nə etməliyəm?', 'Giriş səhifəsindən e-poçt ünvanınızı göndərin — bərpa kodu göndəriləcək.'],
        ];

        foreach ($faqs as [$type, $title, $description]) {
            Faq::updateOrCreate(
                ['type' => $type, 'title->az' => $title],
                ['title' => ['az' => $title, 'en' => $title], 'description' => ['az' => $description, 'en' => $description]]
            );
        }
    }
}
