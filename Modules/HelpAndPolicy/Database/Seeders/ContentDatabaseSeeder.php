<?php

namespace Modules\HelpAndPolicy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpAndPolicy\Entities\Faq;
use Modules\HelpAndPolicy\Entities\LegalTerm;

class ContentDatabaseSeeder extends Seeder
{
    /** FAQ titles left behind by the first version of this seeder. */
    private const LEGACY_FAQ_TITLES = [
        'Sifarişi necə verə bilərəm?',
        'Çatdırılma nə qədər vaxt alır?',
        'Ödənişi hansı üsullarla edə bilərəm?',
        'Promo kod necə istifadə olunur?',
        'Şifrəmi necə dəyişə bilərəm?',
        'Şifrəmi unutmuşam, nə etməliyəm?',
    ];

    /**
     * Storefront content: the legal pages and the FAQ list.
     */
    public function run(): void
    {
        $this->seedLegalPages();
        $this->seedFaqs();
    }

    private function seedLegalPages(): void
    {
        $legal = [
            'about' => [
                'az' => '<h1>Haqqımızda</h1><p>ShopEra — Azərbaycanda insanların və mağazaların elan yerləşdirə, alqı-satqı edə bildiyi onlayn platformadır. Gündəlik ehtiyaclar, geyim, ayaqqabı, aksesuar, gözəllik və digər kateqoriyalarda minlərlə elanı bir araya gətiririk.</p><h3>Missiyamız</h3><p>Alıcı ilə satıcını ən qısa yolla birləşdirmək: aydın elanlar, şəffaf qiymətlər və sürətli əlaqə.</p><h3>Necə işləyir?</h3><p>Hər elan onun sahibi olan satıcıya və ya mağazaya aiddir. Alıcı elan səhifəsindən satıcı ilə birbaşa əlaqə saxlayır, çatdırılma və ödəniş şərtlərini razılaşdırır. ShopEra platformanın operatoru kimi təhlükəsiz və şəffaf mühiti təmin edir.</p><h3>Mağazalar</h3><p>Satıcılar öz mağazasını aça, elanlarını bir yerdə idarə edə və komandası ilə işi bölüşə bilər. Premium və irəli çəkilmiş elanlar alıcıların diqqətinə daha tez çatır.</p><h3>Dəstək</h3><p>Suallarınız üçün “Əlaqə” səhifəsindən yaza və ya WhatsApp vasitəsilə bizimlə əlaqə saxlaya bilərsiniz.</p>',
                'en' => '<h1>About Us</h1><p>ShopEra is an online marketplace in Azerbaijan where people and stores post listings and buy and sell. We bring together thousands of listings across daily needs, fashion, footwear, accessories, beauty and more.</p><h3>Our mission</h3><p>To connect buyers and sellers the shortest way: clear listings, transparent prices and fast contact.</p><h3>How it works</h3><p>Every listing belongs to the seller or store that owns it. Buyers contact the seller directly from the listing page and agree on delivery and payment. ShopEra, as the operator of the platform, keeps the environment safe and transparent.</p><h3>Stores</h3><p>Sellers can open a store, manage all their listings in one place and share the work with a team. Premium and promoted listings reach buyers faster.</p><h3>Support</h3><p>Send us a message from the “Contact” page or reach us on WhatsApp.</p>',
                'ru' => '<h1>О нас</h1><p>ShopEra — онлайн-площадка в Азербайджане, где люди и магазины публикуют объявления, покупают и продают. Мы объединяем тысячи объявлений в категориях повседневных нужд, одежды, обуви, аксессуаров, красоты и других.</p><h3>Наша миссия</h3><p>Соединять покупателя и продавца самым коротким путём: понятные объявления, прозрачные цены и быстрая связь.</p><h3>Как это работает</h3><p>Каждое объявление принадлежит продавцу или магазину. Покупатель связывается с продавцом прямо со страницы объявления и согласовывает доставку и оплату. ShopEra как оператор платформы обеспечивает безопасную и прозрачную среду.</p><h3>Магазины</h3><p>Продавцы могут открыть магазин, управлять всеми объявлениями в одном месте и делить работу с командой. Premium и продвигаемые объявления быстрее привлекают внимание покупателей.</p><h3>Поддержка</h3><p>Напишите нам через страницу «Контакты» или в WhatsApp.</p>',
                'tr' => '<h1>Hakkımızda</h1><p>ShopEra, Azerbaycan’da insanların ve mağazaların ilan verip alım-satım yapabildiği online bir platformadır. Günlük ihtiyaçlar, giyim, ayakkabı, aksesuar, güzellik ve diğer kategorilerde binlerce ilanı bir araya getiriyoruz.</p><h3>Misyonumuz</h3><p>Alıcı ile satıcıyı en kısa yoldan buluşturmak: net ilanlar, şeffaf fiyatlar ve hızlı iletişim.</p><h3>Nasıl çalışır?</h3><p>Her ilan, sahibi olan satıcıya veya mağazaya aittir. Alıcı ilan sayfasından satıcıyla doğrudan iletişim kurar, teslimat ve ödeme koşullarını kararlaştırır. ShopEra platformun operatörü olarak güvenli ve şeffaf ortamı sağlar.</p><h3>Mağazalar</h3><p>Satıcılar kendi mağazasını açabilir, ilanlarını tek yerden yönetebilir ve işi ekibiyle paylaşabilir. Premium ve öne çıkarılmış ilanlar alıcılara daha hızlı ulaşır.</p><h3>Destek</h3><p>“İletişim” sayfasından veya WhatsApp üzerinden bize ulaşabilirsiniz.</p>',
            ],
            'main_page' => [
                'az' => '<h1>Məxfilik Siyasəti</h1><p>Bu siyasət ShopEra platformasından istifadə edərkən şəxsi məlumatlarınızın necə toplandığını, istifadə olunduğunu və qorunduğunu izah edir.</p><h3>Hansı məlumatları toplayırıq</h3><p>Ad və soyad, telefon nömrəsi, e-poçt ünvanı, çatdırılma ünvanı, elan və mağaza məlumatları, həmçinin saytdan istifadə ilə bağlı texniki məlumatlar (brauzer, cihaz, IP ünvanı).</p><h3>Nə üçün istifadə edirik</h3><p>Hesabınızı yaratmaq və qorumaq, elanları göstərmək, alıcı ilə satıcı arasında əlaqəni təmin etmək, dəstək göstərmək, sui-istifadənin qarşısını almaq və xidməti təkmilləşdirmək üçün.</p><h3>Kukilər</h3><p>Sayt girişinizi yadda saxlamaq və istifadə rahatlığını artırmaq üçün kukilərdən istifadə edir. Brauzer parametrlərindən kukiləri silə bilərsiniz.</p><h3>Üçüncü tərəflər</h3><p>Məlumatlar yalnız xidmətin təmin edilməsi üçün zəruri olduqda ödəniş, çatdırılma və texniki infrastruktur təchizatçıları ilə paylaşılır. Məlumatlarınızı reklam məqsədilə satmırıq.</p><h3>Saxlama müddəti</h3><p>Məlumatlar hesabınız aktiv olduğu müddətdə və qanunvericiliyin tələb etdiyi müddət ərzində saxlanılır.</p><h3>Hüquqlarınız</h3><p>Məlumatlarınıza çıxış, onların düzəldilməsi və silinməsi hüququnuz var. Müraciət üçün bizimlə əlaqə saxlayın.</p><h3>Təhlükəsizlik</h3><p>Məlumatlar ISO metodologiyasına uyğun şifrələmə və giriş nəzarəti ilə qorunur; şifrələr açıq formada saxlanılmır.</p><h3>Əlaqə</h3><p>Məxfiliklə bağlı suallarınız üçün “Əlaqə” səhifəsindən və ya WhatsApp vasitəsilə bizə yazın.</p>',
                'en' => '<h1>Privacy Policy</h1><p>This policy explains how your personal data is collected, used and protected when you use the ShopEra platform.</p><h3>What we collect</h3><p>Your name, phone number, email address, delivery address, listing and store details, and technical data about your use of the site (browser, device, IP address).</p><h3>Why we use it</h3><p>To create and protect your account, show listings, connect buyers with sellers, provide support, prevent abuse and improve the service.</p><h3>Cookies</h3><p>The site uses cookies to remember your sign-in and make browsing easier. You can clear cookies in your browser settings.</p><h3>Third parties</h3><p>Data is shared only with payment, delivery and infrastructure providers when necessary to provide the service. We never sell your data for advertising.</p><h3>Retention</h3><p>Data is kept while your account is active and for the period required by law.</p><h3>Your rights</h3><p>You have the right to access, correct and delete your data. Contact us to make a request.</p><h3>Security</h3><p>Data is protected with encryption and access controls; passwords are never stored in plain text.</p><h3>Contact</h3><p>For privacy questions, message us from the “Contact” page or on WhatsApp.</p>',
                'ru' => '<h1>Политика конфиденциальности</h1><p>Эта политика объясняет, как ваши персональные данные собираются, используются и защищаются при работе с платформой ShopEra.</p><h3>Какие данные мы собираем</h3><p>Имя, номер телефона, адрес электронной почты, адрес доставки, данные объявлений и магазина, а также технические сведения об использовании сайта (браузер, устройство, IP-адрес).</p><h3>Для чего мы их используем</h3><p>Для создания и защиты аккаунта, показа объявлений, связи покупателей с продавцами, поддержки, предотвращения злоупотреблений и улучшения сервиса.</p><h3>Cookies</h3><p>Сайт использует cookies, чтобы запоминать вход и делать использование удобнее. Вы можете удалить cookies в настройках браузера.</p><h3>Третьи стороны</h3><p>Данные передаются платёжным, логистическим и инфраструктурным поставщикам только когда это необходимо для сервиса. Мы не продаём ваши данные для рекламы.</p><h3>Срок хранения</h3><p>Данные хранятся, пока аккаунт активен, и в течение срока, установленного законодательством.</p><h3>Ваши права</h3><p>Вы вправе получить доступ, исправить и удалить свои данные. Свяжитесь с нами для запроса.</p><h3>Безопасность</h3><p>Данные защищены шифрованием и контролем доступа; пароли не хранятся в открытом виде.</p><h3>Контакты</h3><p>По вопросам конфиденциальности напишите нам через страницу «Контакты» или в WhatsApp.</p>',
                'tr' => '<h1>Gizlilik Politikası</h1><p>Bu politika, ShopEra platformasını kullanırken kişisel verilerinizin nasıl toplandığını, kullanıldığını ve korunduğunu açıklar.</p><h3>Hangi verileri topluyoruz</h3><p>Ad soyad, telefon numarası, e-posta adresi, teslimat adresi, ilan ve mağaza bilgileri ile siteyi kullanımınıza ilişkin teknik veriler (tarayıcı, cihaz, IP adresi).</p><h3>Neden kullanıyoruz</h3><p>Hesabınızı oluşturmak ve korumak, ilanları göstermek, alıcı ile satıcıyı buluşturmak, destek sunmak, kötüye kullanımı önlemek ve hizmeti geliştirmek için.</p><h3>Çerezler</h3><p>Site, oturumunuzu hatırlamak ve kullanımı kolaylaştırmak için çerezler kullanır. Çerezleri tarayıcı ayarlarınızdan silebilirsiniz.</p><h3>Üçüncü taraflar</h3><p>Veriler yalnızca hizmet için gerekli olduğunda ödeme, teslimat ve altyapı sağlayıcılarıyla paylaşılır. Verilerinizi reklam amacıyla satmayız.</p><h3>Saklama süresi</h3><p>Veriler hesabınız aktif olduğu sürece ve mevzuatın öngördüğü süre boyunca saklanır.</p><h3>Haklarınız</h3><p>Verilerinize erişme, düzeltme ve silme hakkınız vardır.Talep için bizimle iletişime geçin.</p><h3>Güvenlik</h3><p>Veriler şifreleme ve erişim kontrolü ile korunur; şifreler açık metin olarak saklanmaz.</p><h3>İletişim</h3><p>Gizlilikle ilgili sorularınız için “İletişim” sayfasından veya WhatsApp üzerinden yazın.</p>',
            ],
            'register_page' => [
                'az' => '<h1>İstifadə Şərtləri</h1><p>ShopEra platformasından istifadə etməklə bu şərtləri qəbul etmiş olursunuz.</p><h3>Hesab</h3><p>Qeydiyyat zamanı düzgün və aktual məlumat verməlisiniz. Hesabınızın təhlükəsizliyi və şifrənizin qorunması sizin məsuliyyətinizdədir.</p><h3>Elanlar</h3><p>Elan məzmunu, qiyməti və şəkillərin düzgünlüyü satıcının məsuliyyətidir. Eyni məhsulu təkrar-təkrar yerləşdirmək, yanıltıcı qiymət göstərmək və başqa satıcının şəkillərindən icazəsiz istifadə etmək qadağandır.</p><h3>Qadağan olunmuş məzmun</h3><p>Qanunvericiliklə satışı qadağan edilmiş məhsullar, silah, narkotik, saxta sənədlər və saxta brend məhsullar yerləşdirmək qadağandır. Bu qaydalar Azərbaycan Respublikasının qanunvericiliyinə əsaslanır.</p><h3>Mağaza və komanda</h3><p>Mağaza sahibi komanda üzvlərinə verilən icazələrə və onların fəaliyyətinə görə məsuliyyət daşıyır. Komanda dəvət linki yalnız etibarlı şəxslərlə paylaşılmalıdır.</p><h3>Ödənişli xidmətlər</h3><p>Premium və irəli çəkmə kimi görünürlük xidmətləri ödənişlidir. Xidmət haqqı ödənildikdən sonra aktivləşir və geri qaytarılmır, istisna olaraq platformanın texniki səhvi ilə bağlı hallar nəzərdə tutulur.</p><h3>Moderasiya</h3><p>Şərtlərə uyğun olmayan elanları silmək və ya gizlətmək, hesabı məhdudlaşdırmaq hüququnu özümüzdə saxlayırıq.</p><h3>Məsuliyyət</h3><p>ShopEra alqı-satqının tərəfi deyil; satıcı ilə alıcı arasında bağlanan əqdə görə tərəflər məsuliyyət daşıyır. Platforma yalnız elanların yerləşdirildiyi mühiti təqdim edir.</p><h3>Dəyişikliklər</h3><p>Şərtlər dəyişdirilə bilər; aktual versiya həmişə bu səhifədə yerləşdirilir.</p><h3>Əlaqə</h3><p>Şərtlərlə bağlı suallarınız üçün “Əlaqə” səhifəsindən və ya WhatsApp vasitəsilə bizə yazın.</p>',
                'en' => '<h1>Terms of Service</h1><p>By using the ShopEra platform you accept these terms.</p><h3>Account</h3><p>You must provide accurate and up-to-date information when registering. You are responsible for the security of your account and password.</p><h3>Listings</h3><p>Sellers are responsible for the accuracy of listing content, prices and photos. Reposting the same item repeatedly, misleading prices and using another seller’s photos without permission are not allowed.</p><h3>Prohibited content</h3><p>Products banned by law, weapons, drugs, forged documents and counterfeit branded goods may not be listed. We follow the legislation of the Republic of Azerbaijan.</p><h3>Stores and teams</h3><p>The store owner is responsible for the permissions granted to team members and for their activity. Invitation links must be shared only with trusted people.</p><h3>Paid services</h3><p>Visibility services such as Premium and promotion are paid. A service is activated after payment and is not refunded, except for cases caused by a technical error of the platform.</p><h3>Moderation</h3><p>We reserve the right to remove or hide listings that break these terms and to restrict accounts.</p><h3>Liability</h3><p>ShopEra is not a party to the sale; the buyer and the seller are responsible for the deal between them. The platform only provides the place where listings are published.</p><h3>Changes</h3><p>These terms may change; the current version is always published on this page.</p><h3>Contact</h3><p>For questions about the terms, message us from the “Contact” page or on WhatsApp.</p>',
                'ru' => '<h1>Условия использования</h1><p>Используя платформу ShopEra, вы принимаете эти условия.</p><h3>Аккаунт</h3><p>При регистрации вы должны указывать точные и актуальные данные. Вы отвечаете за безопасность аккаунта и пароля.</p><h3>Объявления</h3><p>Продавец отвечает за достоверность содержания объявления, цены и фотографий. Запрещено многократно публиковать один и тот же товар, указывать вводящую в заблуждение цену и использовать чужие фотографии без разрешения.</p><h3>Запрещённый контент</h3><p>Нельзя публиковать товары, запрещённые законом, оружие, наркотики, поддельные документы и контрафактные брендовые товары. Мы руководствуемся законодательством Азербайджанской Республики.</p><h3>Магазины и команды</h3><p>Владелец магазина отвечает за выданные участникам команды права и за их деятельность. Ссылки-приглашения следует передавать только надёжным людям.</p><h3>Платные услуги</h3><p>Услуги видимости, такие как Premium и продвижение, платные. Услуга активируется после оплаты и не возвращается, кроме случаев технической ошибки платформы.</p><h3>Модерация</h3><p>Мы вправе удалять или скрывать объявления, нарушающие эти условия, и ограничивать аккаунты.</p><h3>Ответственность</h3><p>ShopEra не является стороной сделки; за сделку между покупателем и продавцом отвечают стороны. Платформа лишь предоставляет место для публикации объявлений.</p><h3>Изменения</h3><p>Условия могут меняться; актуальная версия всегда публикуется на этой странице.</p><h3>Контакты</h3><p>По вопросам условий напишите нам через страницу «Контакты» или в WhatsApp.</p>',
                'tr' => '<h1>Kullanım Şartları</h1><p>ShopEra platformasını kullanarak bu şartları kabul etmiş olursunuz.</p><h3>Hesap</h3><p>Kayıt sırasında doğru ve güncel bilgiler vermelisiniz. Hesabınızın ve şifrenizin güvenliği sizin sorumluluğunuzdadır.</p><h3>İlanlar</h3><p>İlan içeriğinin, fiyatın ve fotoğrafların doğruluğundan satıcı sorumludur. Aynı ürünü tekrar tekrar yayınlamak, yanıltıcı fiyat göstermek ve başka bir satıcının fotoğraflarını izinsiz kullanmak yasaktır.</p><h3>Yasaklı içerik</h3><p>Kanunen satışı yasak ürünler, silahlar, uyuşturucular, sahte belgeler ve sahte markalı ürünler yayınlanamaz. Azerbaycan Cumhuriyeti mevzuatına uyarız.</p><h3>Mağaza ve ekip</h3><p>Mağaza sahibi, ekip üyelerine verilen izinlerden ve onların faaliyetlerinden sorumludur. Davet bağlantıları yalnızca güvenilir kişilerle paylaşılmalıdır.</p><h3>Ücretli hizmetler</h3><p>Premium ve öne çıkarma gibi görünürlük hizmetleri ücretlidir. Hizmet ödeme sonrası etkinleşir ve platformun teknik hatasından kaynaklanan durumlar dışında iade edilmez.</p><h3>Moderasyon</h3><p>Bu şartlara aykırı ilanları kaldırma veya gizleme ve hesapları kısıtlama hakkını saklı tutarız.</p><h3>Sorumluluk</h3><p>ShopEra satışın tarafı değildir; alıcı ile satıcı arasındaki işlemden taraflar sorumludur. Platform yalnızca ilanların yayınlandığı ortamı sunar.</p><h3>Değişiklikler</h3><p>Şartlar değişebilir; güncel sürüm her zaman bu sayfada yayınlanır.</p><h3>İletişim</h3><p>Şartlarla ilgili sorularınız için “İletişim” sayfasından veya WhatsApp üzerinden yazın.</p>',
            ],
        ];

        foreach ($legal as $type => $html) {
            LegalTerm::updateOrCreate(['type' => $type], ['html' => $html]);
        }
    }

    private function seedFaqs(): void
    {
        // The first release seeded a different FAQ set (email-based password
        // reset, cart checkout); replace those rows with the current content.
        Faq::withTrashed()
            ->whereIn('title->az', self::LEGACY_FAQ_TITLES)
            ->forceDelete();

        $faqs = [
            [
                'general',
                ['ShopEra nədir?', 'What is ShopEra?', 'Что такое ShopEra?', 'ShopEra nedir?'],
                ['ShopEra Azərbaycanda elan və mağaza platformasıdır. Elanların sahibi satıcılar və mağazalardır; ShopEra alıcı ilə satıcını birləşdirən məkandır.', 'ShopEra is a listings and stores marketplace in Azerbaijan. Every listing belongs to a seller or a store; ShopEra is where they meet buyers.', 'ShopEra — площадка объявлений и магазинов в Азербайджане. Каждое объявление принадлежит продавцу или магазину; ShopEra соединяет их с покупателями.', 'ShopEra, Azerbaycan’da ilan ve mağaza platformasıdır. Her ilan bir satıcıya veya mağazaya aittir; ShopEra alıcı ile satıcıyı buluşturur.'],
            ],
            [
                'general',
                ['Necə alış edə bilərəm?', 'How can I buy something?', 'Как совершить покупку?', 'Nasıl alışveriş yapabilirim?'],
                ['Bəyəndiyiniz elanı açın, satıcı ilə telefon, WhatsApp və ya elan səhifəsindəki mesajlaşma ilə əlaqə saxlayın və çatdırılma ilə ödəniş şərtlərini razılaşdırın.', 'Open the listing you like, contact the seller by phone, WhatsApp or the chat on the listing page, and agree on delivery and payment.', 'Откройте понравившееся объявление, свяжитесь с продавцом по телефону, в WhatsApp или через чат на странице объявления и согласуйте доставку и оплату.', 'Beğendiğiniz ilanı açın, satıcıyla telefon, WhatsApp veya ilan sayfasındaki mesajlaşma ile iletişim kurun; teslimat ve ödeme koşullarını kararlaştırın.'],
            ],
            [
                'general',
                ['Dəstək komandası ilə necə əlaqə saxlaya bilərəm?', 'How can I contact support?', 'Как связаться со службой поддержки?', 'Destek ekibiyle nasıl iletişime geçebilirim?'],
                ['Saytın “Əlaqə” səhifəsindən mesaj göndərə, telefonla zəng edə və ya WhatsApp ilə yaza bilərsiniz. İş saatları ərzində cavablandırırıq.', 'Send a message from the “Contact” page, call us or message us on WhatsApp. We reply during working hours.', 'Напишите через страницу «Контакты», позвоните нам или напишите в WhatsApp. Мы отвечаем в рабочие часы.', '“İletişim” sayfasından mesaj gönderebilir, telefonla arayabilir veya WhatsApp’tan yazabilirsiniz. Çalışma saatleri içinde yanıtlıyoruz.'],
            ],
            [
                'listings',
                ['Elan vermək pulludur?', 'Is posting a listing paid?', 'Платное ли размещение объявления?', 'İlan vermek ücretli mi?'],
                ['Adi elan yerləşdirmək pulsuzdur. Premium və irəli çəkmə kimi əlavə görünürlük xidmətləri ödənişlidir; qiymətlər mağaza panelində göstərilir.', 'Posting a regular listing is free. Extra visibility services such as Premium and promotion are paid; prices are shown in the store panel.', 'Размещение обычного объявления бесплатно. Услуги дополнительной видимости, такие как Premium и продвижение, платные; цены указаны в панели магазина.', 'Normal ilan vermek ücretsizdir. Premium ve öne çıkarma gibi ek görünürlük hizmetleri ücretlidir; fiyatlar mağaza panelinde gösterilir.'],
            ],
            [
                'listings',
                ['Elanımı necə premium edim?', 'How do I make my listing Premium?', 'Как сделать объявление Premium?', 'İlanımı nasıl Premium yaparım?'],
                ['Mağaza panelindən və ya elan idarəetmə səhifəsindən elanı seçib “İrəli çək / Premium et” əməliyyatından istifadə edin. Premium elanlar ana səhifədə və kateqoriyalarda öndə görünür.', 'From the store panel or the listing management page choose the listing and use “Promote / Make Premium”. Premium listings lead the home page and category rows.', 'В панели магазина или на странице управления объявлением выберите объявление и нажмите «Продвинуть / Сделать Premium». Premium-объявления показываются первыми на главной странице и в категориях.', 'Mağaza panelinden veya ilan yönetim sayfasından ilanı seçip “Öne çıkar / Premium yap” işlemini kullanın. Premium ilanlar ana sayfada ve kategorilerde önde görünür.'],
            ],
            [
                'listings',
                ['Elanım niyə görünmür?', 'Why is my listing not visible?', 'Почему моё объявление не отображается?', 'İlanım neden görünmüyor?'],
                ['Elan moderasiyadan keçməyibsə, aktivlik müddəti bitibsə və ya deaktiv edilibsə görünmür. Müddəti bitən elanı panelinizdən yeniləyə bilərsiniz.', 'A listing is hidden when it has not passed moderation, its active period has ended or it has been deactivated. You can renew an expired listing from your panel.', 'Объявление не показывается, если не прошло модерацию, истёк срок активности или оно деактивировано. Истёкшее объявление можно обновить из панели.', 'İlan moderasyondan geçmediyse, aktiflik süresi bittiyse veya devre dışı bırakıldıysa görünmez. Süresi dolan ilanı panelinizden yenileyebilirsiniz.'],
            ],
            [
                'stores',
                ['Öz mağazamı necə açıram?', 'How do I open my own store?', 'Как открыть свой магазин?', 'Kendi mağazamı nasıl açarım?'],
                ['Hesabınıza daxil olun və “Mağaza aç” səhifəsindən mağaza adı, təsviri və loqonuzu daxil edin. Mağaza yaradıldıqdan sonra bütün elanlarınızı oradan idarə edə bilərsiniz.', 'Sign in and use the “Open a store” page to enter the store name, description and logo. After the store is created you can manage all your listings from there.', 'Войдите в аккаунт и на странице «Открыть магазин» укажите название, описание и логотип. После создания магазина вы сможете управлять всеми объявлениями оттуда.', 'Hesabınıza giriş yapın ve “Mağaza aç” sayfasından mağaza adı, açıklaması ve logonuzu girin. Mağaza oluşturulduktan sonra tüm ilanlarınızı oradan yönetebilirsiniz.'],
            ],
            [
                'stores',
                ['Komandamı necə idarə edirəm?', 'How do I manage my team?', 'Как управлять командой?', 'Ekibimi nasıl yönetirim?'],
                ['Mağaza sahibi “Komanda” səhifəsindən dəvət linki yaradır və üzvə hansı bölmələrə giriş veriləcəyini seçir: elanlar, irəli çəkmə, sifarişlər, mesajlar, statistika və mağaza məlumatları.', 'The store owner creates an invitation link on the “Team” page and chooses which sections the member may use: listings, promotion, orders, messages, statistics and store details.', 'Владелец магазина создаёт ссылку-приглашение на странице «Команда» и выбирает, к каким разделам получит доступ участник: объявления, продвижение, заказы, сообщения, статистика и данные магазина.', 'Mağaza sahibi “Ekip” sayfasından davet bağlantısı oluşturur ve üyeye hangi bölümlere erişim verileceğini seçer: ilanlar, öne çıkarma, siparişler, mesajlar, istatistikler ve mağaza bilgileri.'],
            ],
            [
                'payments',
                ['Ödəniş necə həyata keçirilir?', 'How does payment work?', 'Как происходит оплата?', 'Ödeme nasıl yapılır?'],
                ['Ödəniş üsulu satıcı və ya mağaza ilə razılaşdırılır: nağd, çatdırılma zamanı kartla və ya köçürmə ilə. Ödənişdən əvvəl satıcının təfərrüatlarını yoxlamağı tövsiyə edirik.', 'The payment method is agreed with the seller or store: cash, card on delivery or a transfer. We recommend checking the seller’s details before paying.', 'Способ оплаты согласуется с продавцом или магазином: наличные, карта при доставке или перевод. Рекомендуем проверить данные продавца до оплаты.', 'Ödeme yöntemi satıcı veya mağaza ile kararlaştırılır: nakit, teslimat sırasında kartla veya havale. Ödeme öncesi satıcının bilgilerini kontrol etmenizi öneririz.'],
            ],
            [
                'delivery',
                ['Çatdırılma necə olur?', 'How does delivery work?', 'Как работает доставка?', 'Teslimat nasıl yapılır?'],
                ['Çatdırılmanın şərtləri, müddəti və qiyməti satıcı ilə razılaşdırılır. Bakı daxilində çatdırılma adətən 1-2 iş günü, digər şəhərlərə 2-5 iş günü çəkir.', 'Delivery terms, timing and price are agreed with the seller. Delivery usually takes 1-2 working days in Baku and 2-5 working days to other cities.', 'Условия, сроки и стоимость доставки согласуются с продавцом. Обычно доставка занимает 1-2 рабочих дня по Баку и 2-5 рабочих дней в другие города.', 'Teslimat koşulları, süresi ve ücreti satıcı ile kararlaştırılır. Teslimat genellikle Bakü içinde 1-2 iş günü, diğer şehirlere 2-5 iş günü sürer.'],
            ],
            [
                'account',
                ['Şifrəmi unutmuşam, nə etməliyəm?', 'I forgot my password, what should I do?', 'Я забыл пароль, что делать?', 'Şifremi unuttum, ne yapmalıyım?'],
                ['Giriş səhifəsindəki “Şifrəmi unutdum?” linkinə keçin. Təhlükəsizlik üçün şifrə bərpası komandamız tərəfindən WhatsApp vasitəsilə aparılır — hazır mesajla bizə yazın.', 'Use the “Forgot password?” link on the sign-in page. For security, password recovery is handled by our team over WhatsApp — message us with the prepared text.', 'Перейдите по ссылке «Забыли пароль?» на странице входа. В целях безопасности восстановление пароля выполняется нашей командой через WhatsApp — напишите нам готовым сообщением.', 'Giriş sayfasındaki “Şifremi unuttum?” bağlantısına gidin. Güvenlik için şifre kurtarma işlemi ekibimiz tarafından WhatsApp üzerinden yapılır — hazır mesajla bize yazın.'],
            ],
            [
                'account',
                ['Şifrəmi və profil məlumatlarımı necə dəyişə bilərəm?', 'How do I change my password and profile details?', 'Как изменить пароль и данные профиля?', 'Şifremi ve profil bilgilerimi nasıl değiştirebilirim?'],
                ['Hesabınıza daxil olduqdan sonra “Tənzimləmələr” səhifəsindən ad, əlaqə məlumatları, ünvan və şifrənizi yeniləyə bilərsiniz.', 'After signing in, use the “Settings” page to update your name, contact details, address and password.', 'После входа обновите имя, контактные данные, адрес и пароль на странице «Настройки».', 'Giriş yaptıktan sonra “Ayarlar” sayfasından ad, iletişim bilgileri, adres ve şifrenizi güncelleyebilirsiniz.'],
            ],
            [
                'security',
                ['Şübhəli elanı və ya istifadəçini necə bildirim?', 'How do I report a suspicious listing or user?', 'Как сообщить о подозрительном объявлении или пользователе?', 'Şüpheli bir ilanı veya kullanıcıyı nasıl bildiririm?'],
                ['“Əlaqə” səhifəsindən və ya WhatsApp vasitəsilə elanın linkini qeyd edərək bizə yazın. Komandamız elanı yoxlayıb zəruri tədbirləri görəcək.', 'Message us from the “Contact” page or on WhatsApp including the listing link. Our team will review it and take the necessary action.', 'Напишите нам через страницу «Контакты» или в WhatsApp, указав ссылку на объявление. Наша команда проверит его и примет необходимые меры.', '“İletişim” sayfasından veya WhatsApp üzerinden ilanın bağlantısını belirterek bize yazın. Ekibimiz ilanı inceleyip gerekli adımları atacak.'],
            ],
        ];

        foreach ($faqs as [$type, [$titleAz, $titleEn, $titleRu, $titleTr], [$descriptionAz, $descriptionEn, $descriptionRu, $descriptionTr]]) {
            Faq::updateOrCreate(
                ['type' => $type, 'title->az' => $titleAz],
                [
                    'title' => ['az' => $titleAz, 'en' => $titleEn, 'ru' => $titleRu, 'tr' => $titleTr],
                    'description' => ['az' => $descriptionAz, 'en' => $descriptionEn, 'ru' => $descriptionRu, 'tr' => $descriptionTr],
                ]
            );
        }
    }
}
