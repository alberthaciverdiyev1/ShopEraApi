<?php

namespace Modules\Blog\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Blog\Entities\Blog;

class BlogDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = [
            [
                'title' => [
                    'az' => '2026-cı ilin Ən Populyar Texnoloji Qadcetləri və Trendləri',
                    'en' => 'Most Popular Tech Gadgets and Trends of 2026',
                    'ru' => 'Самые популярные технические гаджеты и тренды 2026 года',
                    'tr' => '2026 Yılının En Popüler Teknolojik Aletleri ve Trendleri',
                ],
                'slug' => '2026-texnoloji-qadcetler-trendler',
                'description' => [
                    'az' => 'Yeni nəsil smart cihazlar, süni intellekt dəstəkli köməkçilər və gündəlik həyatımızı dəyişdirən innovativ texnoloji məhsullar haqqında ətraflı baxış.',
                    'en' => 'A comprehensive review of next-gen smart devices, AI-powered assistants, and innovative tech products changing our everyday lives.',
                    'ru' => 'Подробный обзор смарт-устройств нового поколения, помощников на базе ИИ и инновационных технологий.',
                    'tr' => 'Yeni nesil akıllı cihazlar, yapay zeka destekli asistanlar ve günlük hayatımızı değiştiren yenilikçi teknoloji ürünleri.',
                ],
                'content' => [
                    'az' => '<p>Müasir dünyada texnologiya hər gün daha sürətlə inkişaf edir. Xüsusilə son dövrlərdə süni intellektin smartfonlara, qulaqlıqlara və ev texnikasına inteqrasiyası istifadəçi təcrübəsini tamamilə yeni səviyyəyə qaldırıb.</p><h4>1. Ağıllı Portativ Qurğular</h4><p>Portativ qadcetlər artıq sadəcə addım saymır, ürək döyüntüsündən tutmuş yuxu keyfiyyətinə qədər bütün biometrik göstəriciləri analiz edir və sizə fərdi sağlamlıq məsləhətləri təqdim edir.</p><h4>2. Ultra Sürətli Doldurma və Uzun Batareya Ömrü</h4><p>Yeni enerji idarəetmə çipləri sayəsində cihazlar 15 dəqiqə ərzində bütün günə yetəcək enerji toplaya bilir. Bu da aktiv həyat tərzi keçirənlər üçün əvəzolunmaz üstünlükdür.</p><p>ShopEra mağazasında ən son texnoloji məhsulları zəmanətlə və sərfəli qiymətlərlə əldə edə bilərsiniz.</p>',
                    'en' => '<p>Technology continues to evolve rapidly. The integration of AI into smartphones, earbuds, and home appliances has elevated user experiences to unprecedented levels.</p><h4>1. Smart Wearables</h4><p>Modern wearables now monitor comprehensive biometric indicators, offering personalized lifestyle and health recommendations.</p><h4>2. Ultra-Fast Charging</h4><p>New energy chips allow full-day charges in as little as 15 minutes, empowering active digital nomads.</p>',
                ],
                'image' => '/assets/images/blog/blogThumb2_1.jpg',
                'category' => 'Texnologiya',
                'author_name' => 'Aygün Məmmədova',
                'tags' => ['Qadcetlər', 'Texnologiya', 'Trendlər', 'İnnovasiya'],
                'views' => 1240,
                'is_active' => true,
                'published_at' => Carbon::now()->subDays(2),
            ],
            [
                'title' => [
                    'az' => 'Payız və Qış Mövsümü üçün Qarderob Yeniləmə Bələdçisi',
                    'en' => 'Wardrobe Refresh Guide for the Autumn & Winter Season',
                    'ru' => 'Гид по обновлению гардероба на осенне-зимний сезон',
                    'tr' => 'Sonbahar ve Kış Sezonu İçin Gardırop Yenileme Rehberi',
                ],
                'slug' => 'payiz-qis-qarderob-beledcisi',
                'description' => [
                    'az' => 'Soyuq havalarda həm zövqlü, həm də isti qalmağın yolları: ən son dəb rəngləri, təbii materiallar və düzgün kombin texnikaları.',
                    'en' => 'How to stay stylish and warm in chilly weather: trending seasonal colors, natural fabrics, and smart layering techniques.',
                    'ru' => 'Как оставаться стильным и в тепле в холодное время года: трендовые цвета, натуральные ткани и многослойность.',
                    'tr' => 'Soğuk havalarda hem şık hem de sıcak kalmanın yolları: trend renkler, doğal kumaşlar ve doğru kombinler.',
                ],
                'content' => [
                    'az' => '<p>Mövsüm dəyişdikcə geyim tərzimiz də yenilənməlidir. Qalın və rahat sviterlər, suya davamlı zərif gödəkcələr və düzgün seçilmiş çəkmələr qış aylarının vazkeçilməzidir.</p><h4>Qat-qat Geyinmə (Layering) Sənəti</h4><p>İncə termal alt paltarı, üzərindən yun jaket və şık palto kombini həm temperatur dəyişikliyinə uyğunlaşmağa, həm də çoxqatlı zövqlü görünüş yaratmağa kömək edir.</p><p>Material seçərkən təbii yun, kaşmir və pambıq tərkibli parçalara üstünlük vermək həm sağlamlıq, həm də geyimin uzunömürlülüyü baxımından önəmlidir.</p>',
                    'en' => '<p>As the seasons change, our wardrobes must adapt. Cozy knits, weather-resistant jackets, and versatile boots are winter essentials.</p><h4>The Art of Layering</h4><p>Layering lightweight thermals with wool knits and tailored coats creates comfortable warmth and timeless elegance.</p>',
                ],
                'image' => '/assets/images/blog/blogThumb2_2.jpg',
                'category' => 'Moda',
                'author_name' => 'Elmir Əliyev',
                'tags' => ['Moda', 'Stil', 'Qarderob', 'Geyim'],
                'views' => 890,
                'is_active' => true,
                'published_at' => Carbon::now()->subDays(5),
            ],
            [
                'title' => [
                    'az' => 'Onlayn Alış-verişdə Təhlükəsizlik və Ən Sərfəli Təkliflər',
                    'en' => 'Security and Smart Deals in Online Shopping',
                    'ru' => 'Безопасность и выгодные предложения в онлайн-шопинге',
                    'tr' => 'Online Alışverişte Güvenlik ve En İyi Fırsatları Yakalama',
                ],
                'slug' => 'onlayn-alis-veris-tehlukesizlik-serfeli-teklifler',
                'description' => [
                    'az' => 'İnternet üzərindən kartla ödəniş edərkən diqqət edilməli təhlükəsizlik qaydaları və xüsusi endirim kampaniyalarından faydalanmağın sirrləri.',
                    'en' => 'Essential security tips for online card payments and proven ways to maximize promotional discounts.',
                    'ru' => 'Советы по безопасности онлайн-платежей и лучшие способы получения максимальных скидок.',
                    'tr' => 'Kartla internet alışverişi yaparken dikkat edilmesi gereken güvenlik adımları ve indirim tüyoları.',
                ],
                'content' => [
                    'az' => '<p>Onlayn alış-veriş sürətli və rahat olsa da, təhlükəsizlik tədbirlərini unutmamaq vacibdir. 3D Secure təsdiqi, SSL şifrələməsi və güvənilən mağazalardan sifariş vermək məlumatlarınızı qoruyur.</p><h4>Promokodlar və Kampaniyalar</h4><p>ShopEra platformasında mütəmadi olaraq təqdim edilən promokodlardan istifadə edərək səbətinizdə əlavə endirimlər əldə edə bilərsiniz.</p>',
                    'en' => '<p>While online shopping offers unmatched convenience, digital safety is key. Always look for 3D Secure verification and trusted platforms.</p>',
                ],
                'image' => '/assets/images/blog/blogThumb2_3.jpg',
                'category' => 'E-ticarət',
                'author_name' => 'Rəşad Qasımov',
                'tags' => ['E-ticarət', 'Təhlükəsizlik', 'Endirimlər', 'Məsləhətlər'],
                'views' => 1520,
                'is_active' => true,
                'published_at' => Carbon::now()->subDays(8),
            ],
            [
                'title' => [
                    'az' => 'Ağıllı Ev Cihazları ilə Həyatınızı Necə Asanlaşdıra Bilərsiniz?',
                    'en' => 'How Smart Home Devices Can Simplify Your Daily Life',
                    'ru' => 'Как умные домашние устройства делают жизнь проще',
                    'tr' => 'Akıllı Ev Cihazları ile Hayatınızı Nasıl Kolaylaştırabilirsiniz?',
                ],
                'slug' => 'agilli-ev-cihazlari-rahat-heyat',
                'description' => [
                    'az' => 'Ağıllı işıqlandırma, robot tozsoranlar və avtomatlaşdırılmış idarəetmə sistemləri ilə evinizdə rahatlıq və enerji qənaəti yaradın.',
                    'en' => 'Bring comfort and energy efficiency into your home with automated lighting, robot vacuums, and smart sensors.',
                    'ru' => 'Создайте комфорт и энергоэффективность с умным освещением и роботами-пылесосами.',
                    'tr' => 'Akıllı aydınlatma, robot süpürgeler ve otomatik sistemlerle evinizde konfor ve tasarruf sağlayın.',
                ],
                'content' => [
                    'az' => '<p>Ağıllı ev texnologiyaları artıq lüks deyil, gündəlik rahatlığın vacib bir hissəsidir. Səhər oyanarkən avtomatik yanan mülayim işıqlar və ya evə çatmamış işə düşən kondisioner həyat keyfiyyətini xeyli artırır.</p>',
                    'en' => '<p>Smart home technologies have evolved from luxuries into everyday convenience tools that save time and reduce utility bills.</p>',
                ],
                'image' => '/assets/images/blog/blogThumb2_4.jpg',
                'category' => 'Texnologiya',
                'author_name' => 'Aygün Məmmədova',
                'tags' => ['Ağıllı Ev', 'Smart Home', 'Texnologiya', 'Rahatlıq'],
                'views' => 740,
                'is_active' => true,
                'published_at' => Carbon::now()->subDays(12),
            ],
            [
                'title' => [
                    'az' => 'Davamlı Alış-veriş və Eko-dost Məhsulların Seçimi',
                    'en' => 'Sustainable Shopping and Choosing Eco-Friendly Products',
                    'ru' => 'Осознанное потребление и выбор экологичных товаров',
                    'tr' => 'Sürdürülebilir Alışveriş ve Çevre Dostu Ürün Tercihleri',
                ],
                'slug' => 'davamli-alis-veris-eko-dost-mehsullar',
                'description' => [
                    'az' => 'Təbiəti qorumaq üçün gündəlik istehlak vərdişlərimizi necə dəyişə bilərik? Təkrar emal olunan materiallar və davamlı məhsul seçimi.',
                    'en' => 'Practical habits to reduce waste: selecting recycled materials, organic cotton, and products built to last.',
                    'ru' => 'Как изменить потребительские привычки ради экологии: выбор переработанных материалов и долговечных вещей.',
                    'tr' => 'Doğayı korumak için tüketim alışkanlıklarımızı nasıl değiştirebiliriz? Geri dönüştürülmüş ve dayanıklı ürünler.',
                ],
                'content' => [
                    'az' => '<p>Eko-dost yaşayış tərzi təkcə təbiətə qayğı deyil, həm də uzunmüddətli qənaətdir. Keyfiyyətli və davamlı məhsul almaq tez-tez yenisini almaq məcburiyyətini aradan qaldırır.</p>',
                    'en' => '<p>Sustainable living begins with conscious choices. Investing in durable, high-quality goods reduces waste and saves money.</p>',
                ],
                'image' => '/assets/images/blog/blogThumb1_1.jpg',
                'category' => 'Həyat tərzi',
                'author_name' => 'Leyla Həsənova',
                'tags' => ['Həyat tərzi', 'Ekologiya', 'Davamlılıq', 'Təbiət'],
                'views' => 610,
                'is_active' => true,
                'published_at' => Carbon::now()->subDays(15),
            ],
            [
                'title' => [
                    'az' => 'Düzgün Aksessuar Seçimi ilə Gündəlik Kombinlərinizi Fərqləndirin',
                    'en' => 'Elevate Your Everyday Outfits with the Right Accessories',
                    'ru' => 'Преобразите повседневные образы правильными аксессуарами',
                    'tr' => 'Doğru Aksesuar Seçimiyle Günlük Kombinlerinizi Farklılaştırın',
                ],
                'slug' => 'duzgun-aksessuar-secimi-kombinler',
                'description' => [
                    'az' => 'Kəmər, saat, çanta və zərgərlik məmulatlarının gücü: ən sadə geyimi belə gözoxşayan və unikal etməyin qaydaları.',
                    'en' => 'The transformative power of watches, bags, belts, and jewelry to make any minimalist outfit shine.',
                    'ru' => 'Сила аксессуаров: как часы, сумки и украшения превращают даже базовый образ в эффектный.',
                    'tr' => 'Kemer, saat, çanta ve takıların gücü: en sade kombini bile göz alıcı kılmanın püf noktaları.',
                ],
                'content' => [
                    'az' => '<p>Aksessuarlar fərdi stilinizi ifadə etməyin ən asan və effektiv yoludur. Keyfiyyətli dəri çanta və zərif saat istənilən geyimə xüsusi dəyər qatır.</p>',
                    'en' => '<p>Accessories are the fastest way to showcase personal identity. A quality leather piece or statement watch anchors any wardrobe.</p>',
                ],
                'image' => '/assets/images/blog/blogThumb1_2.jpg',
                'category' => 'Moda',
                'author_name' => 'Elmir Əliyev',
                'tags' => ['Aksessuar', 'Moda', 'Kombin', 'Gözəllik'],
                'views' => 1100,
                'is_active' => true,
                'published_at' => Carbon::now()->subDays(18),
            ],
        ];

        foreach ($posts as $data) {
            Blog::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
