# GEMINI.md

Bu dosya, Antigravity ve Gemini Code Assist araçlarının **Snaker** projesinde çalışırken uyması gereken kuralları, mimari yapıyı, kodlama standartlarını ve geliştirme kılavuzlarını tanımlar.

---

## 1. Proje Genel Bakışı

**Snaker** — Laravel 12 tabanlı, modüler monolit mimarisine sahip çok dilli (`az`, `en`, `ru`, `tr`) bir e-ticaret ve pazaryeri (marketplace) platformudur. Mobil uygulamalar REST API'yi tüketir; web mağazası ve admin paneli Laravel içinde Blade olarak sunulur.

### Teknoloji Yığını
- **Backend**: PHP 8.3+, Laravel 12, PostgreSQL (testlerde in-memory SQLite), Redis, Laravel Sanctum, Spatie Permission, Spatie Translatable.
- **Web**: Blade + Vite 7 + Tailwind 4, mevcut Bootstrap tabanlı mağaza teması.
- **Entegrasyonlar**: Starex (kargo), Firebase Cloud Messaging (FCM), Laravel Reverb (realtime), Sentry, BunnyCDN.

---

## 2. Dizin Yapısı

```
Snaker/
├── GEMINI.md                     # Ana Antigravity proje kuralları
├── AGENTS.md                     # Çoklu ajan yönergeleri
├── .geminiignore                 # Antigravity bağlam dışı bırakma listesi
├── app/                          # Paylaşılan çekirdek mantık (Helpers, Enums, Jobs, Services)
├── Modules/                      # nwidart/laravel-modules iş alanı modülleri
│   ├── Product/                  # Ürün modülü
│   ├── Order/                    # Sipariş modülü
│   ├── Brand/                    # Marka modülü
│   └── ...                       # Diğer modüller
├── config/                       # Laravel konfigürasyonları
├── database/                     # Ana migration ve seeders
├── routes/                       # Kök route tanımları (api.php, web.php, admin.php)
└── resources/
    ├── css/                      # app.css (admin) ve storefront.css
    ├── js/                       # app.js (admin) ve storefront.js
    └── views/
        ├── admin/                # Admin paneli Blade sayfaları
        ├── components/storefront # Mağaza Blade bileşenleri
        └── storefront/           # Mağaza sayfaları
```

---

## 3. Sık Kullanılan Komutlar

```bash
# Geliştirme ortamı (Server + Queue + Log + Vite)
composer dev

# Sadece API sunucusu
php artisan serve

# Testler (Test öncesi config:clear tavsiye edilir)
composer test
php artisan test
php artisan test --filter=ExampleTest

# Kod Biçimlendirme (Laravel Pint)
./vendor/bin/pint
./vendor/bin/pint Modules/Product

# Veritabanı işlemleri
php artisan migrate
php artisan module:migrate <ModulAdi>

# Asset derlemesi (admin + storefront)
npm run build
```

---

## 4. Backend Mimari ve Kod Konvansiyonları

### 4.1. Katmanlama İlkesi
- **Controller → Service → Entity** hiyerarşisi zorunludur.
- Controller'lar ince (lean) olmalı; iş mantığı ve veritabanı sorguları `Modules/<Modul>/Services/` katmanında yer almalıdır.
- Modeller `Modules/<Modul>/Entities/` altında yaşar ve `Modules\<Modul>\Entities` namespace'ine sahiptir.
- Doğrulama mantığı controller içine yazılmaz, `Modules/<Modul>/Http/Requests/*Request.php` sınıflarında `prepareForValidation()` ile birlikte ele alınır.
- Çıktılar `Modules/<Modul>/Http/Resources/` ile formatlanır.

### 4.2. API Cevap Standardı
Tüm API cevapları `responseHelper()` global fonksiyonu ile döndürülür:
```php
return responseHelper('messages.success', 200, $resource);
```
- Mobil istemciler `?is_application=1` gönderdiğinde sarmalayıcısız düz veri döner.
- Web istemcilerinde `{success, status_code, message, data, meta}` standart zarfı korunur.

### 4.3. Çok Dillilik (Localization)
- Modellerde çevrilebilir alanlar `spatie/laravel-translatable` ile yönetilir:
  ```php
  public array $translatable = ['name', 'description'];
  ```
- Diller: `az` (varsayılan), `en`, `ru`, `tr`.
- Otomatik çeviri gerektiğinde `App\Helpers\TranslateHelper::translate()` kullanılır.

### 4.4. Blade Mağaza Katmanı
- Uygulama seviyesinde yalnızca `app/Http/Controllers/Admin/` ve `app/Http/Controllers/Web/` bulunur; API controller'ları modüllerin içindedir.
- `Web/` controller'ları DB sorgusu yazmaz ve `app/Services/` kullanmaz. İlgili modül controller'ını `Request::create(...)` + `setUserResolver(...)` ile çağırır, `validateResolved()` ile modül FormRequest'ini doğrular ve JSON zarfını view verisine çevirir.
- Blade view'ları DB sorgusu yazmaz; header gibi ortak veriler view composer üzerinden modül controller'larından gelir.
- Blade view'ları `resources/views/storefront/` ve `resources/views/components/storefront/` altında yaşar.
- Etkileşimler `resources/js/storefront.js` içinde saf JavaScript ile yazılır.

---

## 5. Antigravity Ajanı İçin Kritik Kurallar ve Kısıtlamalar

1. **Önce Oku ve Analiz Et**: Bir bileşen veya modülde değişiklik yapmadan önce mevcut dosyaları, tipleri ve fonksiyonları incele.
2. **Katmanlamayı Koru**: Controller içine iş mantığı veya doğrudan Eloquent sorgusu yazma.
3. **Sırları Asla Sızdırma**: `.env`, `storage/firebase/*.json`, Stripe/Starex API anahtarları gibi gizli bilgileri hiçbir zaman loglara veya yanıtlara dökme.
4. **API Sözleşmelerini Bozma**: Frontend ve mobil uygulamaların ortak kullandığı endpoint alan adlarını değiştirmeden önce geriye dönük uyumluluğu koru.
5. **Kapsamı Dar Tut**: İlgisiz dosyalarda toplu yeniden adlandırma veya stil değişiklikleri yapma.
6. **Güvenli Git ve Komut Kullanımı**: `git push --force`, `git reset --hard` veya üretim veritabanına zarar verebilecek `migrate:fresh` gibi komutları asla onay almadan çalıştırma.
