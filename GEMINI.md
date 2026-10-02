# GEMINI.md

Bu dosya, Antigravity ve Gemini Code Assist araçlarının **ShopEra** projesinde çalışırken uyması gereken kuralları, mimari yapıyı, kodlama standartlarını ve geliştirme kılavuzlarını tanımlar.

---

## 1. Proje Genel Bakışı

**ShopEra** — Laravel 12 tabanlı, modüler monolit mimarisine sahip çok dilli (`az`, `en`, `ru`, `tr`) bir e-ticaret ve pazaryeri (marketplace) platformudur.
Proje iki ana katmandan oluşur:
1. **Backend (API-first)**: Laravel 12 + `nwidart/laravel-modules` ile yapılandırılmış REST API. Mobil uygulamalar ve web ön yüzü aynı REST API'yi tüketir.
2. **Frontend**: `resources/frontend/` dizininde yer alan, **Svelte 5 (Runes mode)**, **SvelteKit 2**, **TypeScript** ve **Vite 8** ile geliştirilen modern mağaza ön yüzü.

### Teknoloji Yığını
- **Backend**: PHP 8.3+, Laravel 12, PostgreSQL (testlerde in-memory SQLite), Redis, Laravel Sanctum, Spatie Permission, Spatie Translatable.
- **Frontend**: Svelte 5.56+ (Runes zorunlu), SvelteKit 2.63+, TypeScript 6+, Vite 8, Bootstrap 5.3, Swiper 14, Fancybox 6.
- **Entegrasyonlar**: Starex (kargo), Firebase Cloud Messaging (FCM), Laravel Reverb (realtime), Sentry, BunnyCDN.

---

## 2. Dizin Yapısı

```
ShopEra/
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
├── routes/                       # Kök route tanımları (api.php, web.php)
└── resources/
    ├── frontend/                 # Svelte 5 / SvelteKit Frontend Uygulaması
    │   ├── GEMINI.md             # Frontend'e özel detaylı Svelte 5 kuralları
    │   ├── src/
    │   │   ├── lib/              # API servisleri, tipler, tema ve bileşenler
    │   │   │   ├── components/   # Svelte bileşenleri (cards, layout, pages, shop)
    │   │   │   ├── theme/        # DOM davranışları ve stil helper'ları
    │   │   │   ├── api.ts        # Merkezi API istemcisi
    │   │   │   ├── products.ts   # Ürün tipleri ve API çağrıları
    │   │   │   ├── categories.ts # Kategori ağacı ve mağaza filtreleri
    │   │   │   └── shop.ts       # Arama, filtreleme ve sayfalama
    │   │   └── routes/           # SvelteKit dosya tabanlı route yapısı
    │   ├── static/               # Statik varlıklar (css, js, resimler)
    │   ├── package.json
    │   ├── svelte.config.js / vite.config.ts
    │   └── tsconfig.json
    └── views/                    # Gerekli durumlarda Blade şablonları
```

---

## 3. Sık Kullanılan Komutlar

### Backend (Laravel)
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
```

### Frontend (SvelteKit)
```bash
# Frontend dizinine geçiş
cd resources/frontend

# Geliştirme sunucusu
npm run dev

# Tip ve Svelte denetimi
npm run check

# Üretim derlemesi
npm run build

# Önizleme
npm run preview
```

---

## 4. Svelte 5 Frontend Geliştirme Kuralları (ÖNEMLİ ODAK)

Frontend `resources/frontend/` dizininde yaşar ve **Svelte 5** mimarisiyle yazılmıştır. `vite.config.ts` içinde `runes: true` zorunludur.

### 4.1. Svelte 5 Runes Kuralı
- **Legacy Svelte 3/4 sözdizimi KESİNLİKLE KULLANILMAZ.**
  - ❌ `export let propName;` yerine 👉 **`let { propName }: { propName: Type } = $props();`**
  - ❌ `$: doubled = count * 2;` yerine 👉 **`const doubled = $derived(count * 2);`**
  - ❌ Karmaşık türetmelerde 👉 **`const pageNumbers = $derived.by(() => { ... });`**
  - ❌ Reaktif değişkenler için `let count = 0;` yerine 👉 **`let count = $state(0);`**
  - ❌ Yan etkiler için `$: if (x) doSomething();` yerine 👉 **`$effect(() => { ... });`**
  - ❌ `<slot />` yerine 👉 **`{@render children()}`** ve **`{#snippet name(...)}`**
  - ❌ `on:click={handleClick}` yerine 👉 **`onclick={handleClick}`** (HTML standart olay isimleri)

### 4.2. API Entegrasyonu & Veri Akışı
- Tüm API istekleri `$lib/api.ts` içindeki `apiGet` ve `apiGetWithMeta` fonksiyonları üzerinden yapılmalıdır.
- Backend Laravel API cevap zarfı (`{ success, message, data, meta }`) döner. `apiGet<T>` bu zarfı otomatik açarak doğrudan `data` alanını döner.
- Sayfalama veya ek filtre verisi gereken durumlarda `apiGetWithMeta<T>` kullanılır.
- URL sorgu parametrelerinde Laravel dizi formatı gözetilir: `arrayParam[] = value`. `$lib/api.ts:buildQuery()` bu dönüşümü otomatik sağlar.

### 4.3. TypeScript ve Tip Güvenliği
- Tüm veri modelleri (`ApiProduct`, `ApiCategory`, `ShopFilters`, `ShopFacets`) TypeScript arayüzleriyle tiplenmelidir.
- `any` kullanımından kaçınılmalı, bilinmeyen alanlar `unknown` veya açık tiplerle ele alınmalıdır.
- Kod yazıldıktan sonra `npm run check` çalıştırılarak Svelte/TypeScript hataları doğrulanmalıdır.

### 4.4. DOM, Harici Kütüphaneler ve SSR Güvenliği
- Tarayıcıya bağımlı kütüphaneler (`@fancyapps/ui`, `swiper`) doğrudan modül tepesinde değil, **`onMount` içinde dinamik `import()` ile** yüklenmelidir (SSR hatasını önlemek için).
- Şablonun orijinal jQuery bağımlılıkları `$lib/theme/behaviors.ts` içinde saf TypeScript/DOM ile yeniden yazılmıştır (`initPageBehaviors()`). Yeni DOM widget'ları eklerken bu yaklaşım sürdürülmelidir.
- Sayfa geçişlerinde `afterNavigate` kancası ile görsel maskeler ve dinamik widget'lar yeniden tetiklenmelidir.

> Svelte tarafı ile ilgili daha detaylı kılavuz için: [`resources/frontend/GEMINI.md`](file:///home/albert/Workspace/FoxSoft/ShopEra/resources/frontend/GEMINI.md) dosyasına başvurun.

---

## 5. Backend Mimari ve Kod Konvansiyonları

### 5.1. Katmanlama İlkesi
- **Controller → Service → Entity** hiyerarşisi zorunludur.
- Controller'lar ince (lean) olmalı; iş mantığı ve veritabanı sorguları `Modules/<Modul>/Services/` katmanında yer almalıdır.
- Modeller `Modules/<Modul>/Entities/` altında yaşar ve `Modules\<Modul>\Entities` namespace'ine sahiptir.
- Doğrulama mantığı controller içine yazılmaz, `Modules/<Modul>/Http/Requests/*Request.php` sınıflarında `prepareForValidation()` ile birlikte ele alınır.
- Çıktılar `Modules/<Modul>/Http/Resources/` ile formatlanır.

### 5.2. API Cevap Standardı
Tüm API cevapları `responseHelper()` global fonksiyonu ile döndürülür:
```php
return responseHelper('messages.success', 200, $resource);
```
- Mobil istemciler `?is_application=1` gönderdiğinde sarmalayıcısız düz veri döner.
- Web istemcilerinde `{success, status_code, message, data, meta}` standart zarfı korunur.

### 5.3. Çok Dillilik (Localization)
- Modellerde çevrilebilir alanlar `spatie/laravel-translatable` ile yönetilir:
  ```php
  public array $translatable = ['name', 'description'];
  ```
- Diller: `az` (varsayılan), `en`, `ru`, `tr`.
- Otomatik çeviri gerektiğinde `App\Helpers\TranslateHelper::translate()` kullanılır.

---

## 6. Antigravity Ajanı İçin Kritik Kurallar ve Kısıtlamalar

1. **Önce Oku ve Analiz Et**: Bir bileşen veya modülde değişiklik yapmadan önce mevcut dosyaları, tipleri ve fonksiyonları incele.
2. **Svelte 5 Runes Uyumunu Koru**: Asla eski Svelte 3/4 kalıplarını (`export let`, `$:`, `on:click`) kod tabanına sokma.
3. **Sırları Asla Sızdırma**: `.env`, `storage/firebase/*.json`, Stripe/Starex API anahtarları gibi gizli bilgileri hiçbir zaman loglara veya yanıtlara dökme.
4. **API Sözleşmelerini Bozma**: Frontend ve mobil uygulamaların ortak kullandığı endpoint alan adlarını değiştirmeden önce geriye dönük uyumluluğu koru.
5. **Kapsamı Dar Tut**: İlgisiz dosyalarda toplu yeniden adlandırma veya stil değişiklikleri yapma.
6. **Güvenli Git ve Komut Kullanımı**: `git push --force`, `git reset --hard` veya üretim veritabanına zarar verebilecek `migrate:fresh` gibi komutları asla onay almadan çalıştırma.
