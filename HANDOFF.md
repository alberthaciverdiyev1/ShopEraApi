# Snaker — Devir Teslim / Kalan İşler

Bu dosya, oturum kesildiğinde kaldığı yerden devam edebilmek için yazıldı.
Aşağıdaki "Durum" bölümü mevcut hâli özetler, "Kalan" bölümü öncelik sırasıyla ilerlemeli.

---

## 1. Nasıl çalıştırılır

```bash
# Uygulama (API + Blade storefront + admin paneli, port 8000)
cd /home/albert/Workspace/FoxSoft/ShopEra
php artisan serve

# Kuyruk + zamanlayıcı (ayrı terminaller)
php artisan queue:work
php artisan schedule:work

# Asset derlemesi
npm run build
```

> Proje Docker kullanmaz; host'ta PHP-FPM/nginx ile çalışır.
> Mağaza artık tamamen Blade ile sunulur; ayrı bir Node/Svelte süreci yoktur.

- **API adresi:** `/api` (Laravel içinde)
- **Test kullanıcısı (Dressso import):** `musteri@dressso.local` / `Musteri1234!`

---

## 2. ✅ Durum

### Mağaza
- Tüm sayfalar Blade'e taşındı: ana sayfa, shop, kategoriler, ürün detayı, blog,
  mağaza profili, içerik sayfaları, giriş/kayıt, dashboard, ayarlar, istek listesi,
  sepet, checkout, sipariş geçmişi, elan oluşturma/yönetimi, mağaza paneli.
- SvelteKit uygulaması (`resources/frontend/`) kaldırıldı; `public/storefront`,
  `public/_app` ve `storefront:deploy` komutu da silindi.
- `sitemap.xml` ve `robots.txt` Laravel route'ları olarak eklendi.
- Ürün detayında alıcı–satıcı sohbeti ve yorum yazma formu eklendi.

### Mimari
- `app/Services/Storefront/` tamamen kaldırıldı; uygulama seviyesinde API
  controller'ı yok. `app/Http/Controllers/` yalnızca `Admin/` ve `Web/` içerir.
- Web controller'ları DB sorgusu yazmaz ve servis kullanmaz; ilgili modül
  controller'ını `Request::create(...)` + `setUserResolver(...)` ile çağırıp JSON
  zarfını view verisine çevirir (`app/Core/Concerns/InteractsWithModuleControllers.php`).
- Ortak header verisi `App\Core\ViewComposers\StorefrontHeaderComposer` üzerinden gelir.
- Paylaşılan trait/arayüz/enum/helper/support sınıfları `app/Core/` altında;
  uygulama kökünde servis veya job yok. Domain servisleri ve job'ları ilgili
  modülün `Services/` ve `Jobs/` klasörlerinde (ör. `Modules/Order/Jobs`).
- Taşınan uçlar artık modüllerde: `Product/Routes/api.php` (`/home`),
  `Setting/Routes/api.php` (`/contact`, `/data-version`, `/promo-blocks`).

### Veri
- Dressso verileri `php artisan dressso:import` ile aktarıldı (katalog, kullanıcı,
  mağaza, sipariş, adres). Komut yeniden çalıştırılabilir (idempotent).

### Devre dışı
- Destek sohbeti (canlı destek widget'ı, dashboard "Mesajlar" sekmesi ve admin
  `/admin/chat`) view'larda `@if (false)` ile kapatıldı. Altyapı yerinde duruyor.

---

## 3. ⏳ Kalan işler

- **OTP / e-posta akışları** (şifre sıfırlama, doğrulama) — bu turda kapsam dışı.
- **Çok dillilik:** Blade mağaza şu an yalnızca Azerice metinlerle çalışıyor;
  az/en/ru/tr dil değiştirici taşınmadı.
- **Admin katmanı:** `app/Http/Controllers/Admin/` altındaki controller'lar hâlâ
  doğrudan modül `Entities` sınıflarıyla DB sorgusu yapıyor (24 dosya). "Admin de
  doğrudan DB işlemi yapamaz" kuralına uyması için bunların modül controller/service
  çağrılarına çevrilmesi gerekiyor — henüz başlanmadı.
- **Testler:** Yeni web controller'ları ve `DresssoImportService` için
  feature testleri yok.
- **Dağıtım:** `deploy/deploy.sh` Svelte adımlarından arındırıldı; üretim geçişi
  (nginx catch-all + asset build) doğrulanmalı.

---

## 4. Bilinmesi gerekenler (gotcha)

1. **Yetki modeli:** `PermissionDatabaseSeeder` her izni `developer/admin/user/manager`
   rollerine verir. Bu yüzden oraya **admin-only izin eklemeyin** (`view orders-admin` gibi).
2. **Sınıflandırıcı engeli:** `.env` düzenlemesi ve `auth:sanctum`/`permission:` kaldırma
   işlemleri otomatik olarak engellenebiliyor. Alternatif: yetkiyi **tanımlayıp role vermek**.
3. **Önbellek:** `GET /faq` ve bazı liste uçları `Cache::remember` kullanır — seed sonrası
   `php artisan cache:clear` gerekir.
4. **`migrate:fresh`** bu şemada tabloları düşüremiyor; bunun yerine
   `DROP DATABASE snaker` + `CREATE DATABASE` + `migrate --seed` kullan.
5. **Süreçler:** test için headless Chrome süreçleri arka planda kalabilir (`pkill -x chrome`).

---

## 5. Doğrulama komutları

```bash
# Rotalar
for r in / /shop /cart /dashboard /faq /terms /privacy; do
  echo -n "$r -> "; curl -s -o /dev/null -w '%{http_code}\n' "http://127.0.0.1:8000$r"
done

# Build
npm run build

# API örneği
curl -H "Accept: application/json" "http://127.0.0.1:8000/api/theme"
```
